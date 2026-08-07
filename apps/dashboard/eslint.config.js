import eslint from '@eslint/js';
import prettier from 'eslint-config-prettier';
import tseslint from 'typescript-eslint';
import vue from 'eslint-plugin-vue';

export default tseslint.config(
    { ignores: ['dist/**', 'node_modules/**'] },
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    ...vue.configs['flat/recommended'],
    prettier,
    {
        files: ['src/**/*.{js,ts,vue}'],
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                AbortController: 'readonly',
                HTMLElement: 'readonly',
                KeyboardEvent: 'readonly',
            },
        },
    },
    {
        files: ['cypress/**/*.ts', 'cypress.config.ts'],
        languageOptions: {
            globals: { cy: 'readonly', Cypress: 'readonly' },
        },
    },
    {
        files: ['scripts/**/*.mjs'],
        languageOptions: {
            globals: { console: 'readonly', process: 'readonly' },
        },
    },
    {
        files: ['src/**/*.vue'],
        languageOptions: { parserOptions: { parser: tseslint.parser } },
    },
);
