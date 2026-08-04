# ToggleFlow

> Open-source, self-hosted feature management platform built with Laravel and Vue.

ToggleFlow helps software teams separate **deployment** from **release** by providing a centralized platform for managing feature flags across environments.

## Why ToggleFlow?

Modern teams deploy frequently, but not every feature is ready to be released immediately. ToggleFlow allows developers to ship code safely while giving product and QA teams control over when features become available.

## Product

- 🚩 Boolean feature flags with environment-specific release state
- 🌍 Isolated Development, Staging, and Production environments
- 🔑 Opaque, environment-scoped evaluation credentials
- 📜 Transactionally consistent management audit history
- ⚡ Versioned evaluation REST API with safe fallbacks
- 🖥️ Responsive Vue management dashboard

The active product program strengthens backend and frontend ownership, establishes a
Nuxt UI design foundation, and redesigns the current management workflows before
progressive delivery expands evaluation behavior. Percentage rollouts, targeting,
OpenFeature and SDK integrations, team governance, and production operations follow
through the maintained roadmap.

## Available Capabilities

- Authentication
- Projects
- Environments
- Boolean Feature Flags
- Audit Logs
- API Keys
- Evaluation REST API

See [Product Requirements](docs/05-product-requirements.md),
[Product Strategy](docs/07-product-strategy.md), and the
[Product Roadmap](docs/08-roadmap.md) for product scope and delivery direction.

## Documentation

Start with the [Documentation Overview](docs/00-overview.md). The documentation is
organized into:

- product definition and direction in `docs/00` through `docs/08`;
- system design and contracts in `docs/architecture/`;
- contributor and delivery guidance in `docs/engineering/`; and
- accepted architecture decisions in `docs/decisions/`.

## Tech Stack

- PHP 8.3 and Laravel 12
- Vue 3 Composition API and TypeScript 6
- Vue Router, Vite 7, and Tailwind CSS 4
- MySQL 8.4
- Laravel Sanctum for first-party SPA cookie authentication
- Pest 3, Laravel Pint, Larastan/PHPStan, ESLint, Prettier, Vitest, and Cypress

Redis is intentionally not required for evaluation correctness. MySQL remains the
authoritative configuration store until an approved, evidence-based architecture
decision changes that responsibility.

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
php artisan db:seed
cd ../dashboard
pnpm install --frozen-lockfile
pnpm build
```

Start each independently in its own terminal:

```bash
cd apps/platform-api && php artisan serve
cd apps/dashboard && pnpm dev
```

The SPA is available at `http://localhost:5173` and proxies `/dashboard`, `/api`, and `/sanctum`
to Laravel at `http://127.0.0.1:8000`. Laravel Sanctum's
`/sanctum/csrf-cookie` endpoint initializes CSRF protection before sign-in.

The default local configuration enables a deterministic release fixture after `php
artisan db:seed`. The command is idempotent and may be run again without duplicating
the fixture:

```text
Email: owner@toggleflow.test
Password: toggleflow-demo
Project: Checkout Service
Environments: Development, Staging, Production
Feature flag: new-checkout (disabled in every environment)
```

Demo seeding and credential display are restricted to local or explicit demo
environments. Set `TOGGLEFLOW_DEMO_ENABLED=false` when local demo access is not
required; production never enables it from this setting alone.

## Evaluate a feature flag

Create an environment API key in the dashboard, store the one-time credential in the
client application's secret manager, and send it as a bearer token:

```bash
curl --fail-with-body \
  --header "Accept: application/json" \
  --header "Authorization: Bearer ${TOGGLEFLOW_API_KEY}" \
  "http://127.0.0.1:8000/api/v1/flags/new-checkout"
```

A configured boolean flag returns its environment-specific value:

```json
{
  "data": {
    "key": "new-checkout",
    "value": true,
    "reason": "STATIC"
  }
}
```

Missing, archived, and unconfigured flags return `false` with a machine-readable
reason. Client applications must still use a local fallback for network failures,
server errors, and timeouts. Use a different environment key for each deployment,
never ship server-side keys in public browser bundles, and redact bearer values from
logs and error reports.

## Demo the release-control workflow

The seeded fixture supports the complete release demonstration without database
edits or a pre-generated credential:

1. Sign in with the documented demo account and open **Checkout Service**.
2. Open **API keys**, issue a key for Production, copy its one-time value into the
   local `TOGGLEFLOW_API_KEY` shell variable, and acknowledge the disclosure.
3. Run the cURL evaluation above and observe `false` for `new-checkout`.
4. Open **Feature flags** and enable Development. Evaluate with the Production key
   again and confirm Production remains `false`.
5. Enable Production after reviewing its impact confirmation. The next evaluation
   returns `true` without an application deployment.
6. Disable Production to demonstrate rollback. The next evaluation returns `false`.
7. Open **Audit history** and verify both Production changes include the demo owner
   and timestamps.
8. Revoke the Production key, then repeat the evaluation and verify the stable
   `401 INVALID_API_KEY` response.

Create the key through the dashboard for each demonstration. ToggleFlow never seeds,
stores, or documents a reusable plaintext environment credential.

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
- First-party dashboard endpoints live below `/dashboard` and use Sanctum cookie and
  CSRF authentication. Session creation, lookup, and deletion use
  `/dashboard/auth/session`.
- Public evaluation endpoints live below `/api/v1` and use separate opaque,
  environment-scoped credentials.
- The Vue SPA owns dashboard navigation and consumes Laravel's JSON APIs. In
  production, a reverse proxy should preferably expose the dashboard and API through
  one HTTPS origin even though they are independently built applications.
- The evaluation API remains a module of `apps/platform-api`. It should
  become a separate deployable service only after measured scaling or reliability
  needs justify that operational boundary.

## License

MIT
