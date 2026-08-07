# ADR 004: Feature-oriented Vue Architecture

**Status:** Accepted and implemented by TF-24

## Context

The MVP dashboard grouped product code by technical type (`pages`, `services`,
`stores`, and `types`). That structure obscured workflow ownership and made future
feature growth likely to increase global coupling. The dashboard must retain its Vue
3/Vite SPA topology, routes, Sanctum behavior, API contracts, accessibility, and
request-state behavior while making ownership and dependency direction explicit.

## Decision

`apps/dashboard/src` has three ownership roots (plus Vite's `env.d.ts`):

- `app`: bootstrap, root shell, Pinia creation, routing, global CSS, the public
  foundation page, and cross-feature composition.
- `features`: `authentication`, `projects`, `feature-flags`, `credentials`,
  `audit-history`, and `dashboard`. Each owns its API operations, domain types,
  pages, composables, stores, utilities, and tests where those artifacts exist.
- `shared`: product-neutral HTTP transport, redirect safety, and UI primitives.

The project-overview route component is app-owned because it intentionally composes
the projects and feature-flags public APIs. This avoids a reverse projects-to-flags
feature dependency.

### Ownership inventory

| Before | Final owner |
| --- | --- |
| `App.vue`, `app.ts`, router, bootstrap, CSS, foundation page | `app` |
| sign-in page, auth API/types/store/tests | `features/authentication` |
| projects pages/API/types/context store/tests | `features/projects` |
| flag pages/API/types/tests | `features/feature-flags` |
| API-key page/API/types/tests | `features/credentials` |
| audit page/API/types/description utility/tests | `features/audit-history` |
| dashboard page/API/types/tests | `features/dashboard` |
| feature-specific reactive workflows | the owning feature's `composables` directory |
| cross-feature project-overview workflow and shell navigation behavior | `app/composables` |
| Axios transport/session-expiry registration | `shared/api` |
| safe redirect validation | `shared/navigation` |
| accessible dialog | `shared/ui` |

The obsolete layer-first product folders were removed after every source file had a
final owner.

### Dependency matrix

| Source | May depend on |
| --- | --- |
| `app` | feature public entry points and `shared` |
| a feature | its internals, `shared`, and another feature's `index.ts` |
| `shared` | third-party libraries and other `shared` code only |

The intentional feature graph is acyclic: feature flags, credentials, and audit
history may depend on projects; dashboard may depend on audit history. Cross-feature
imports use `@features/<owner>` exactly. Feature internals are not public contracts.
Features do not import app implementation.

TypeScript, Vite, and Vitest define matching `@app`, `@features`, and `@shared`
aliases. `scripts/check-boundaries.mjs` runs within `pnpm lint`, checks the live source
graph for prohibited direction, deep cross-feature imports, and cycles regardless of
whether an import uses an alias or a relative path. Temporary allowed/prohibited
source-tree fixtures exercise the same scanner so rule behavior fails closed. This
narrow repository script avoids adding a general dependency-analysis package for the
current graph.

## Migration and rollback

Migration proceeded in releasable ownership slices: boundary baseline, app/shared,
authentication, projects, feature flags, credentials, audit history, dashboard, then
legacy removal. It changes import and file ownership only. A rollback restores the
previous paths and imports together; no data, API, route, deployment, or backend
rollback is involved.

## Alternatives rejected

- Retaining layer-first global folders leaves ownership and dependency direction
  implicit.
- Mirroring Laravel modules would organize the UI around backend structure rather
  than user workflows.
- Micro-frontends, Nuxt, SSR, or a separate repository add deployment/runtime cost
  without solving current ownership.
- Moving domain models into `shared` hides feature dependencies and creates a domain
  dumping ground.

## Consequences

Feature work has a clear owner and public boundary, and dependency violations fail
the normal lint gate. Cross-feature composition may require an app-owned component.
Maintainers must deliberately promote genuinely domain-neutral code to `shared` and
keep feature entry points narrow. No product behavior or authorization boundary is
created by this source architecture.
