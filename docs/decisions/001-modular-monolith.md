# ADR 001: Laravel Modular Monolith

**Status:** Accepted

ToggleFlow uses one Laravel modular monolith in `apps/platform-api`. Bounded modules
provide ownership and dependency direction without introducing distributed runtime
failure modes. Service extraction requires measured scaling, security, or independent
release need and a new architecture decision.

## Context

The original Laravel application grouped project-owned code by technical layer. That
layout obscured ownership as management and evaluation developed distinct HTTP,
authentication, traffic, and compatibility requirements. TF-22 adopts bounded
modules before planned backend capabilities extend those paths.

This is an internal ownership refactor. It does not change product behavior, public
or management contracts, persistence, deployment topology, or the authoritative
MySQL boundary.

## Decision

Project-owned backend code is organized below the existing `App\\` PSR-4 root:

```text
app/
├── Core/
└── Modules/
    ├── Identity/
    ├── ReleaseManagement/
    └── Evaluation/
```

- **Core** owns application composition and genuinely shared framework mechanics. It
  contains no product workflow or model.
- **Identity** owns dashboard users, Sanctum session authentication, login/logout,
  and login rate limiting.
- **ReleaseManagement** owns projects, environments, flags, environment state,
  evaluation credential persistence and lifecycle, audit history, and dashboard
  reads.
- **Evaluation** owns `/api/v1`, evaluation orchestration and results, environment-key
  request authentication, public error contracts, and evaluation rate limiting.

Each product module has one Laravel service provider and owns its route file,
bindings, policies, and rate limiters. Providers are composed from
`bootstrap/providers.php`; modules are not separate Composer packages or Laravel
applications.

## Dependency rules

| Consumer | May depend on | Must not depend on |
| --- | --- | --- |
| Core | Laravel/framework support | Identity, ReleaseManagement, Evaluation |
| Identity | Core | ReleaseManagement, Evaluation |
| ReleaseManagement | Core, Identity actor model | Evaluation |
| Evaluation | Core, ReleaseManagement public credential/domain types | Identity HTTP/application code |

Dependencies must remain explicit and acyclic. Credential storage, hashing, issue,
revocation, and usage recording remain in ReleaseManagement. Evaluation consumes the
narrow `AuthenticatesEnvironmentKeys` contract exposed by ReleaseManagement and the
minimum authenticated credential identity; request data cannot choose project or
environment scope.

Interfaces remain limited to demonstrated capabilities such as credential
authentication and auditable subjects. Traits remain limited to shared mechanics such
as the audit relationship. Models own local invariants and relationships; application
actions own multi-model workflows, transactions, audit writes, and external effects.

## Enforcement and tests

Pest architecture tests in `tests/Architecture` enforce namespace dependencies,
Core purity, provider/route ownership, management/evaluation separation, and removal
of the legacy layer-first namespaces. Behavioral tests remain in repository-level
`tests/Feature` and `tests/Unit` directories for standard Laravel discovery. PHPStan
continues to analyze `app` and `routes`.

No additional dependency tool is introduced while Pest can express the required
rules reproducibly. A future enforcement-tool change requires evidence of a gap and
an update to this decision.

## Migration and rollback

Migration proceeds in releasable slices: enforcement baseline, provider/Core
foundation, Identity, ReleaseManagement, Evaluation, then legacy removal and full
verification. Route names and prefixes, middleware priority, limiters, exception
rendering, policies, database schema, audit transactions, serialized fields, and API
envelopes remain compatibility baselines.

Each slice is mechanically reversible before merge. If a slice fails its focused or
regression checks, revert that slice rather than retaining duplicate old and new
owners. TF-22 completes only when every current backend class has one module owner,
legacy application directories are absent, clean installation and the primary demo
flow pass, and required CI succeeds.

## Consequences

Ownership and future extraction seams become explicit without adding distributed
runtime or persistence failure modes. The cost is namespace/provider churn and
architecture-test maintenance. Core must be actively kept small, and cross-module
convenience imports are rejected when they violate the dependency direction.
