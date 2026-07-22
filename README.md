# ToggleFlow

> Open-source, self-hosted feature management platform built with Laravel and Vue.

ToggleFlow helps software teams separate **deployment** from **release** by providing a centralized platform for managing feature flags across environments.

## Why ToggleFlow?

Modern teams deploy frequently, but not every feature is ready to be released immediately. ToggleFlow allows developers to ship code safely while giving product and QA teams control over when features become available.

## Product Direction

- 🚩 Feature Flag Management
- 🌍 Environment-specific configuration
- 🔑 API key authentication
- 📜 Audit logs
- ⚡ REST API
- 🖥️ Modern Vue dashboard

Percentage rollouts, user targeting, SDKs, and team permissions are planned after the
boolean-flag MVP.

## MVP 0.1 Scope

- Authentication
- Projects
- Environments
- Boolean Feature Flags
- Audit Logs
- API Keys
- Evaluation REST API

The MVP is the first production-quality milestone, not the final product. Percentage
rollouts, targeting, SDKs, organizations, and permissions remain part of the planned
evolution of ToggleFlow.

## Documentation

See the `docs/` directory for the product documentation.

- 00-overview.md
- 01-product-vision.md
- 02-problem.md
- 03-target-users.md
- 04-features.md
- 05-non-functional-requirements.md
- 06-mvp-product-requirements.md
- 07-domain-and-architecture.md
- 08-api-contract.md
- 09-delivery-plan.md
- 10-architecture-and-flow-diagrams.md
- 11-authentication-and-api-key-decision.md
- 12-frontend-architecture-and-design-system.md
- 13-product-to-delivery-workflow.md
- 14-engineering-and-coding-standards.md
- 15-monorepo-application-structure.md
- 16-git-branch-pr-ci-workflow.md
- roadmap.md

## Tech Stack

- PHP 8.3 and Laravel 12
- Vue 3 Composition API and TypeScript 6
- Vue Router, Vite 7, and Tailwind CSS 4
- MySQL 8.4
- Laravel Sanctum for first-party SPA cookie authentication
- Pest 3, Laravel Pint, Larastan/PHPStan, ESLint, Prettier, Vitest, and Cypress

Redis is intentionally not required for MVP correctness.

## Prerequisites

- PHP 8.3 with the extensions required by Laravel and MySQL
- Composer 2.7 or newer
- Node.js 22.12–24 and pnpm 11
- MySQL 8.4 or another MySQL 8 release supported by Laravel

## Installation

```bash
cd apps/platform-api
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database and a local user, then update only your untracked
`.env` with those credentials. The committed example intentionally contains no
password or generated application key.

```bash
php artisan migrate
cd ../dashboard
pnpm install --frozen-lockfile
pnpm build
```

Start each independently in its own terminal:

```bash
cd apps/platform-api && php artisan serve
cd apps/dashboard && pnpm dev
```

The SPA is available at `http://localhost:5173` and proxies `/api` and `/sanctum`
to Laravel at `http://127.0.0.1:8000`. Laravel Sanctum's
`/sanctum/csrf-cookie` endpoint is available for the authentication work delivered by
TF-3.

## Quality Checks

```bash
cd apps/platform-api
composer test
composer analyse
composer format:test
composer validate --strict
cd ../dashboard
pnpm typecheck
pnpm lint
pnpm format:check
pnpm test
pnpm test:e2e
pnpm build
```

Backend tests use SQLite in memory for fast isolation. Backend CI additionally runs
migrations and backend quality gates against MySQL 8.4. Frontend CI independently
runs type checking, linting, formatting, Vitest, the production build, and Cypress.

## Application Boundaries

- ToggleFlow is one monorepo, with `apps/platform-api` for the
  Laravel backend and `apps/dashboard` for the Vue SPA.
- First-party management endpoints live below `/api/management` and will use Sanctum
  cookie authentication.
- Public evaluation endpoints live below `/api/v1` and will use separate opaque,
  environment-scoped credentials in later tickets.
- The Vue SPA owns dashboard navigation and consumes Laravel's JSON APIs. In
  production, a reverse proxy should preferably expose the dashboard and API through
  one HTTPS origin even though they are independently built applications.
- The evaluation API remains a module of `apps/platform-api` for the MVP. It should
  become a separate deployable service only after measured scaling or reliability
  needs justify that operational boundary.

## License

MIT
