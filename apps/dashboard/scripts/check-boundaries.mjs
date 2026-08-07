import {
    existsSync,
    mkdtempSync,
    mkdirSync,
    readFileSync,
    readdirSync,
    rmSync,
    statSync,
    writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, isAbsolute, join, relative, resolve, sep } from 'node:path';

const extensions = ['.ts', '.js', '.vue'];
const importPattern = /(?:from\s+|import\s*\(\s*|import\s+)['"]([^'"]+)['"]/g;

const sourceFiles = (directory) =>
    readdirSync(directory).flatMap((entry) => {
        const path = resolve(directory, entry);
        return statSync(path).isDirectory()
            ? sourceFiles(path)
            : extensions.some((extension) => path.endsWith(extension))
              ? [path]
              : [];
    });

const sourceOwner = (sourceRoot, path) => {
    const pathFromRoot = relative(sourceRoot, path);

    if (pathFromRoot.startsWith(`..${sep}`) || isAbsolute(pathFromRoot)) return null;

    const segments = pathFromRoot.split(sep);
    if (segments[0] === 'features' && segments[1]) return { area: 'feature', feature: segments[1] };
    if (['app', 'shared'].includes(segments[0])) return { area: segments[0], feature: null };
    return null;
};

const existingImportTarget = (path) => {
    const candidates = [
        path,
        ...extensions.map((extension) => `${path}${extension}`),
        ...extensions.map((extension) => join(path, `index${extension}`)),
    ];

    return candidates.find((candidate) => existsSync(candidate) && !statSync(candidate).isDirectory()) ?? path;
};

const importTarget = (sourceRoot, importingFile, specifier) => {
    if (specifier.startsWith('.')) return existingImportTarget(resolve(dirname(importingFile), specifier));

    const aliases = {
        '@app': join(sourceRoot, 'app'),
        '@features': join(sourceRoot, 'features'),
        '@shared': join(sourceRoot, 'shared'),
    };

    for (const [alias, target] of Object.entries(aliases)) {
        if (specifier === alias) return existingImportTarget(target);
        if (specifier.startsWith(`${alias}/`)) {
            return existingImportTarget(join(target, specifier.slice(alias.length + 1)));
        }
    }

    return null;
};

const isPublicFeatureEntry = (sourceRoot, target, feature) =>
    target === existingImportTarget(join(sourceRoot, 'features', feature));

const cycles = (graph) => {
    const found = new Set();

    const visit = (feature, trail = []) => {
        if (trail.includes(feature)) {
            found.add([...trail.slice(trail.indexOf(feature)), feature].join(' -> '));
            return;
        }

        for (const dependency of graph.get(feature) ?? []) visit(dependency, [...trail, feature]);
    };

    for (const feature of graph.keys()) visit(feature);
    return [...found];
};

const scanSourceTree = (sourceRoot) => {
    const violations = [];
    const graph = new Map();

    for (const file of sourceFiles(sourceRoot)) {
        const owner = sourceOwner(sourceRoot, file);
        if (!owner) continue;

        for (const match of readFileSync(file, 'utf8').matchAll(importPattern)) {
            const specifier = match[1];
            const target = importTarget(sourceRoot, file, specifier);
            const targetOwner = target ? sourceOwner(sourceRoot, target) : null;
            if (!target || !targetOwner) continue;

            const fileName = relative(sourceRoot, file);

            if (owner.area === 'shared' && ['feature', 'app'].includes(targetOwner.area)) {
                violations.push(`${fileName}: shared cannot import ${specifier}`);
            }

            if (owner.area === 'feature' && targetOwner.area === 'app') {
                violations.push(`${fileName}: features cannot import app implementation ${specifier}`);
            }

            if (owner.area === 'feature' && targetOwner.area === 'feature' && targetOwner.feature !== owner.feature) {
                if (!isPublicFeatureEntry(sourceRoot, target, targetOwner.feature)) {
                    violations.push(`${fileName}: cross-feature import must use public entry point (${specifier})`);
                }

                const dependencies = graph.get(owner.feature) ?? new Set();
                dependencies.add(targetOwner.feature);
                graph.set(owner.feature, dependencies);
            }
        }
    }

    for (const cycle of cycles(graph)) violations.push(`cyclic feature dependency: ${cycle}`);
    return violations;
};

const writeFixture = (sourceRoot, files) => {
    for (const [path, contents] of Object.entries(files)) {
        const target = join(sourceRoot, path);
        mkdirSync(dirname(target), { recursive: true });
        writeFileSync(target, contents);
    }
};

const fixtureCases = [
    {
        name: 'allowed relative imports stay within their owner',
        files: {
            'shared/api/http.ts': 'export const http = {};\n',
            'shared/api/client.ts': "import './http';\n",
            'features/projects/api/projects.ts': 'export const projects = {};\n',
            'features/projects/pages/ProjectsPage.ts': "import '../api/projects';\n",
        },
        expected: [],
    },
    {
        name: 'relative shared-to-feature import',
        files: {
            'features/projects/index.ts': 'export {};\n',
            'shared/api/http.ts': "import '../../features/projects';\n",
        },
        expected: ['shared cannot import'],
    },
    {
        name: 'relative feature-to-app import',
        files: {
            'app/router.ts': 'export {};\n',
            'features/projects/page.ts': "import '../../app/router';\n",
        },
        expected: ['features cannot import app implementation'],
    },
    {
        name: 'relative deep cross-feature import',
        files: {
            'features/projects/api/projects.ts': 'export {};\n',
            'features/credentials/api/keys.ts': "import '../../projects/api/projects';\n",
        },
        expected: ['cross-feature import must use public entry point'],
    },
    {
        name: 'relative cyclic feature imports',
        files: {
            'features/projects/index.ts': "import '../feature-flags';\n",
            'features/feature-flags/index.ts': "import '../projects';\n",
        },
        expected: ['cyclic feature dependency'],
    },
];

const fixtureViolations = [];

for (const fixture of fixtureCases) {
    const fixtureRoot = mkdtempSync(join(tmpdir(), 'toggleflow-boundaries-'));
    const sourceRoot = join(fixtureRoot, 'src');

    try {
        writeFixture(sourceRoot, fixture.files);
        const violations = scanSourceTree(sourceRoot);
        const unexpected = fixture.expected.filter((expected) => !violations.some((item) => item.includes(expected)));

        if (unexpected.length > 0 || (fixture.expected.length === 0 && violations.length > 0)) {
            fixtureViolations.push(`boundary fixture failed: ${fixture.name} (${violations.join('; ') || 'accepted'})`);
        }
    } finally {
        rmSync(fixtureRoot, { recursive: true, force: true });
    }
}

const violations = [...scanSourceTree(resolve('src')), ...fixtureViolations];

if (violations.length > 0) {
    console.error(violations.join('\n'));
    process.exitCode = 1;
} else {
    console.log('Frontend ownership boundaries are valid.');
}
