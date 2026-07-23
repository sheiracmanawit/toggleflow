# Domain and Architecture

## 1. Architectural Goals

ToggleFlow should be simple to operate in the MVP and straightforward to extend. The
architecture prioritizes clear domain boundaries, secure evaluation, auditability,
and a stable public API over premature distribution or microservices.

The recommended implementation is one monorepo containing two application
workspaces: a modular Laravel backend in `apps/platform-api` and a Vue 3 dashboard in
`apps/dashboard`. They are independently built, but together form one ToggleFlow
product. The separation keeps frontend tooling and deployment concerns explicit
without prematurely splitting the Laravel domain into microservices.

The accepted repository and runtime boundaries are recorded in
[Monorepo Application Structure](15-monorepo-application-structure.md).

See [Architecture and Flow Diagrams](10-architecture-and-flow-diagrams.md) for the
system context, container view, domain relationships, user flow, and evaluation
sequence described in this document.

Authentication choices and the decision not to use Passport for MVP evaluation are
recorded in [Authentication and API Key Decision](11-authentication-and-api-key-decision.md).

The Vue SPA structure, Tailwind design system, page composition, responsive behavior,
and accessibility rules are defined in
[Frontend Architecture and Design System](12-frontend-architecture-and-design-system.md).

## 2. System Context

ToggleFlow has two distinct access paths:

1. **Management path:** a human uses the Vue dashboard and authenticated Laravel
   endpoints to manage projects, flags, environments, and keys.
2. **Evaluation path:** an application uses an environment key to obtain a flag value.

These paths share domain data but have different security, performance, and response
requirements. Keeping their controllers, authentication, and response objects
separate makes later caching and SDK development safer.

## 3. Core Domain Model

### User

Represents the authenticated owner in MVP 0.1. A user owns many projects. A future
organization membership layer can replace direct ownership without changing the
project-to-environment or flag-to-state relationships.

### Project

Represents one software application or service. It owns environments, flags, API
keys through environments, and audit events. It has an active or archived lifecycle.

### Environment

Represents an isolated deployment context such as Development, Staging, or
Production. It belongs to a project and owns flag states and evaluation keys.

### FeatureFlag

Represents a stable decision point in application code. Its key is the public
identity used during evaluation. Descriptive metadata belongs to the flag, while the
enabled value belongs to each environment-specific state.

### EnvironmentFlag

Joins an environment and feature flag and stores the current boolean value. This
separation is the most important extensibility decision in the MVP. Later releases
can associate ordered rules, variations, prerequisites, and version metadata with
this environment-specific configuration.

### ApiKey

Represents a revocable credential scoped to one environment. The database stores a
lookup prefix and secure hash, never the recoverable secret.

### AuditEvent

Represents an append-only record of a meaningful management action. It references an
actor when available, a project, and a polymorphic or explicit subject. Metadata must
be filtered to prevent passwords, tokens, and complete API keys from being stored.

## 4. Suggested Relationships

```text
User 1 ── * Project
Project 1 ── * Environment
Project 1 ── * FeatureFlag
Environment 1 ── * EnvironmentFlag * ── 1 FeatureFlag
Environment 1 ── * ApiKey
Project 1 ── * AuditEvent
User 0..1 ── * AuditEvent
```

## 5. Suggested Persistence Constraints

- `projects.slug` is unique per owner for the MVP.
- `environments.key` is unique per project.
- `feature_flags.key` is unique per project.
- `environment_flags` is unique on `(environment_id, feature_flag_id)`.
- API-key hashes are unique and indexed.
- Audit events are indexed by project and creation time.
- Foreign keys prevent cross-project orphan records.
- Archivable resources use an explicit lifecycle or soft deletion consistently.

Database constraints supplement application validation; they are not optional.

## 6. Application Layers

### HTTP and UI Layer

Controllers parse requests, invoke application actions, and return resources. They
must not implement evaluation rules or scatter audit logic across request methods.
Vue pages coordinate presentation and forms; reusable state and API behavior should
live in composables or stores where appropriate.

The authenticated management interface is an independently built Vue 3 single-page
application. Vue Router owns dashboard navigation, and the SPA communicates with the
Laravel application through JSON management endpoints authenticated by Sanctum. In
production, a reverse proxy should preferably expose both applications through one
HTTPS origin. Server-side rendering is not required for MVP 0.1 because the
management interface is private, interactive, and has no search-indexing requirement.

First-party dashboard JSON endpoints use the `/dashboard` namespace and are registered
from `routes/dashboard.php`. The public, versioned evaluation contract alone uses
`/api/v1` and is registered from `routes/api.php`. The route files preserve distinct
authentication, controller, response, and rate-limiting boundaries inside Laravel.

For the MVP project lifecycle, the dashboard management boundary provides
owner-scoped operations to list active projects, create a project, read project
details, update mutable metadata, and archive a project through a deliberate command.
Project resources expose intentional fields rather than raw Eloquent models. Project
details include Development, Staging, and Production in stable order. The slug is
immutable after creation, and archival retains environments and history while
removing the project from ordinary active views.

### Application Layer

Use focused actions or services for operations such as:

- `CreateProject`
- `CreateFeatureFlag`
- `SetEnvironmentFlagState`
- `IssueEnvironmentKey`
- `RevokeEnvironmentKey`
- `EvaluateFeatureFlag`

State changes and their audit records should be committed in the same transaction.

### Domain Layer

Models, value objects, policies, and domain rules enforce invariants. Evaluation
should return a result object containing the value, reason, and optional metadata,
not just a raw boolean. That result can grow without changing the evaluator's role.

### Infrastructure Layer

Laravel persistence, Sanctum session authentication, hashing, logging, and optional
Redis caching belong here. Redis should be introduced only after correctness exists;
the evaluator interface should make that addition transparent to callers.

The public evaluation endpoints remain a dedicated module and HTTP boundary within
the Laravel application for MVP 0.1. A future `apps/evaluation-api` is an extraction
option, not a planned MVP workspace. Extraction requires measured independent
scaling, availability, or deployment needs and a documented data-ownership decision.

## 7. Evaluation Design

The MVP evaluator performs these steps:

1. Authenticate and resolve the environment from the API key.
2. Find an active flag by project and key.
3. Find its configuration for the resolved environment.
4. Return the stored boolean with reason `STATIC`.
5. Return the safe fallback with reason `FLAG_NOT_FOUND`, `FLAG_ARCHIVED`, or
   `CONFIGURATION_MISSING` when required.

A later rule engine can preserve the same entry point:

```text
evaluate(environment, flagKey, context, defaultValue) -> EvaluationResult
```

It can then evaluate prerequisites, ordered targeting rules, percentage allocation,
and a final default rule. No speculative rule tables are needed for MVP 0.1.

## 8. Percentage Rollout Extension

Percentage rollout must be deterministic. A stable hash of the flag, environment,
and targeting identifier should map the same user to the same bucket. Requests must
not use random selection, because users would change variation between evaluations.

The future request context may contain:

- A required stable targeting key
- Optional user ID
- Optional email
- Custom string, number, or boolean attributes

The MVP API may accept no context, but its evaluation service should be callable with
an empty context object so adding context does not require redesigning management
models.

## 9. Security Boundaries

- Dashboard authentication and evaluation authentication are separate concerns.
- Every management query is scoped through the authenticated owner's project.
- An environment key cannot select another project or environment.
- API secrets are generated with cryptographically secure randomness.
- Key comparison uses secure hashing and avoids storing plaintext.
- Rate limiting applies to authentication and evaluation endpoints.
- Audit metadata uses an allowlist rather than serializing full models or requests.
- Production errors do not expose stack traces or database details.
- CORS is configured deliberately for browser consumers; it is not globally open by
  accident.

MVP authentication is intentionally split by actor:

- Human dashboard users authenticate with Laravel Sanctum using the first-party SPA
  session flow.
- Client applications authenticate evaluation requests with opaque, hashed API keys
  scoped to one environment.
- Laravel Passport and OAuth 2.0 client credentials are not used for MVP evaluation.
  Passport may be reconsidered for a future public management API requiring standard
  OAuth clients, short-lived tokens, delegated access, or granular scopes.

## 10. Performance and Reliability

The database-backed evaluator is acceptable for MVP 0.1 if it meets the documented
latency target under a representative local test. Later optimization should use:

- Compound indexes for flag lookup and environment state
- Short-lived cache entries by environment and flag key
- Explicit cache invalidation after committed management changes
- Client-side SDK caching and caller-provided defaults

The authoritative result is the persisted configuration. If caching is added, tests
must prove that enabling or disabling a flag invalidates stale values.

## 11. Audit Strategy

Audit events describe business actions, not raw HTTP requests. Recommended action
names include:

- `project.created`
- `project.updated`
- `project.archived`
- `feature_flag.created`
- `feature_flag.updated`
- `feature_flag.archived`
- `feature_flag.enabled`
- `feature_flag.disabled`
- `api_key.created`
- `api_key.revoked`

Evaluation requests should not be stored in the audit log. High-volume evaluation
telemetry is a separate future concern with different retention requirements.

## 12. Evolution to Organizations

Direct project ownership keeps the MVP small. When teams are introduced:

1. Add organizations and organization memberships.
2. Associate projects with organizations.
3. Migrate each existing user into a personal organization.
4. Replace owner checks with membership permissions.

This migration should be planned before v1.0. The MVP should centralize authorization
in policies so the migration does not require rewriting every controller.

## 13. Testing Strategy

- Unit-test evaluator outcomes and domain invariants.
- Feature-test authentication, ownership, validation, and API responses.
- Test that flags and API keys cannot cross project or environment boundaries.
- Test audit records in the same scenarios that change state.
- Test secrets are shown once and not persisted in plaintext.
- Add a small browser-level happy-path test only if time permits after backend feature
  coverage is reliable.
