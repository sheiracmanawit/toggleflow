# Engineering and Coding Standards

## 1. Purpose

These standards define how ToggleFlow code is structured, named, tested, reviewed,
and documented. They apply to human contributors and project agents.

Checked-in formatter, linter, static-analysis, and test configuration is executable
enforcement of this document. When prose and tooling disagree, stop and resolve the
conflict rather than silently following whichever is more convenient.

## 2. Guiding Principles

- Prefer clear, explicit behavior over clever abstraction.
- Keep changes limited to the approved ticket and its necessary supporting work.
- Deliver complete vertical behavior, including authorization, errors, and tests.
- Protect security and domain invariants at server and database boundaries.
- Keep business behavior independent of controllers and Vue pages.
- Add abstractions after repeated need is demonstrated, not in anticipation of future
  roadmap features.
- Optimize for maintainability and observable correctness before caching or premature
  performance work.
- Leave documentation and tests at least as accurate as they were before the change.

### KISS, DRY, and abstraction

Apply **KISS** (*Keep It Simple, Stupid*) and **DRY** (*Don't Repeat Yourself*)
together:

- Choose the simplest design that clearly satisfies the current ticket and protects
  the documented invariants.
- Remove duplicated knowledge, business rules, security decisions, API shapes, and
  interaction behavior—not merely code that happens to look similar.
- Keep a first simple use local when extraction would add indirection without reuse or
  clearer ownership.
- At the second use, compare why the code is similar. Extract it when both places must
  change together because they represent the same concept or rule.
- A third genuine use is a strong signal to extract unless the duplication is
  intentionally independent and documented.
- Centralize authorization, credential handling, evaluation rules, audit behavior,
  transaction boundaries, and public response construction immediately when multiple
  entry points could otherwise implement them differently. Do not wait for a third
  occurrence where divergence would create a security or correctness risk.
- Do not create a generic abstraction for speculative roadmap behavior or combine
  code that is only superficially similar and likely to evolve independently.

“Used more than once” triggers a design check, not an automatic class or component.
The abstraction must have one clear responsibility, a meaningful name, and a natural
owner.

Choose the extraction that matches the responsibility:

| Repeated responsibility | Preferred location |
| --- | --- |
| State-changing application workflow | Focused Laravel Action |
| Reusable domain calculation or orchestration | Domain or application Service |
| Authorization decision | Laravel Policy |
| HTTP input validation | Form Request or shared validation rule |
| Stable JSON representation | API Resource |
| Repeated Vue markup and interaction | UI or domain Component |
| Reusable reactive Vue behavior | Composable |
| Repeated typed HTTP operation or normalization | Frontend service module |
| Shared visual value or variant | Semantic design token or UI primitive |
| Small, pure, domain-neutral transformation | Narrow helper or utility |
| Repeated test construction | Factory state, dataset, or focused test helper |

Do not use a catch-all `Helper`, `Manager`, `Utils`, global store, or base class as a
dumping ground. Prefer a little obvious duplication over the wrong abstraction, then
refactor when shared intent is demonstrated.

## 3. Repository Organization

ToggleFlow is one monorepo with two application workspaces. The target repository
shape is:

```text
apps/
├── platform-api/    # Laravel management and MVP evaluation backend
└── dashboard/       # Vue 3 TypeScript SPA
packages/            # Added only for real shared contracts or published SDKs
docs/
```

Do not create an empty package or a third service merely to reserve a future name.
Follow [Monorepo Application Structure](15-monorepo-application-structure.md) and do
not place application-owned source or dependency manifests at the repository root.

Within `apps/platform-api`, follow Laravel conventions while making application
actions and public boundaries easy to find.

Suggested backend organization:

```text
apps/platform-api/app/
├── Actions/
│   ├── Projects/
│   ├── FeatureFlags/
│   ├── ApiKeys/
│   └── Audit/
├── Domain/
│   └── Evaluation/
├── Http/
│   ├── Controllers/
│   │   ├── Management/
│   │   └── Api/V1/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
└── Providers/
```

This is a starting convention, not a requirement to create empty directories or
speculative layers. Follow existing Laravel structure when it remains clear.

The frontend organization is defined in
[Frontend Architecture and Design System](12-frontend-architecture-and-design-system.md).

## 4. PHP Standards

### Formatting and language

- Follow PSR-12.
- Use Laravel Pint as the formatting authority.
- Use the PHP version selected during TF-2 consistently in local development and CI.
- Add `declare(strict_types=1);` to project-owned PHP classes unless a framework-
  generated file or compatibility constraint provides a documented reason not to.
- Use typed parameters, return values, properties, enums, and value objects where
  they improve correctness.
- Avoid mixed and unbounded arrays at application boundaries when a request object,
  data object, resource, or documented array shape is clearer.
- Prefer immutable values for evaluation results and credential issuance results.
- Use `final` for concrete application actions and services that are not designed for
  inheritance.

### Naming

- Classes use PascalCase.
- Methods and variables use camelCase.
- Database attributes and array payload keys use snake_case where they represent API
  or persistence fields.
- Booleans read as predicates: `enabled`, `archived`, `isRevoked()`, `canEvaluate()`.
- Action classes use explicit verb phrases such as `CreateProject`,
  `SetEnvironmentFlagState`, `IssueEnvironmentKey`, and `EvaluateFeatureFlag`.
- Avoid vague names such as `Manager`, `Helper`, `Utility`, `process()`, or
  `handleData()` without a narrow, obvious responsibility.

### Enums, interfaces, and traits

Use a string-backed enum when a value belongs to a closed, named vocabulary and its
stored or published representation must remain stable. Typical examples are
lifecycle states, audit actions, evaluation reasons, and credential statuses.

- Give enum cases domain names and keep backing values compatible with persistence
  and API contracts.
- Cast Eloquent attributes to their enum when hydrated application code should be
  typed.
- Use the enum at write sites and use its backing value only at persistence-query or
  serialization boundaries that require a scalar.
- Add a case through the same product and compatibility review required for changing
  the underlying domain vocabulary.
- Do not use an enum for user-defined values, extensible configuration, arbitrary
  metadata, or an intentionally open set of third-party identifiers.
- Do not leave duplicate string literals after introducing an enum for the same
  concept.

Use an interface when a caller needs one capability across multiple concrete types,
or when it protects an external or architectural boundary. Name the capability
directly, such as `Auditable`.

- Keep contracts small and behavior-focused.
- Do not create an interface for every concrete action or service.
- Do not put methods on a contract merely because one implementation happens to have
  them.

Use a trait when multiple classes share the same narrow implementation and PHP
composition is clearer than duplication.

- Prefer traits for relationships, scopes, casts, or small capability mechanics that
  do not need injected services.
- Pair a trait with an interface when generic callers require a contract and the
  implementing models also share mechanics.
- Keep traits stateless where practical and make side effects explicit.
- Do not resolve services from the container, own transactions, perform authorization,
  or orchestrate multi-model writes from a trait.
- Do not use a trait solely to avoid a few lines of intentionally different code.

### Error handling

- Do not swallow exceptions or convert all failures into a generic success response.
- Translate expected domain and validation failures at the HTTP boundary.
- Let unexpected failures reach centralized reporting while returning the documented
  non-sensitive production response.
- Never expose stack traces, SQL, hashes, bearer headers, or internal identifiers in
  public error messages.
- Use exceptions for exceptional or rejected operations, not ordinary branching.

## 5. Laravel Standards

### Controllers and requests

- Keep controllers thin: receive the request, coordinate authorization, invoke one
  application operation, and return a response.
- Use Form Requests for nontrivial validation and authorization input rules.
- Use Laravel Policies for project ownership and management authorization.
- Use dedicated API Resources for JSON responses; do not expose Eloquent models
  directly.
- Separate management controllers from `/api/v1` evaluation controllers.
- Do not put domain rules in route closures.

### Routes

- Group three or more routes when they share meaningful middleware, URI prefixes,
  name prefixes, parameter constraints, controller context, or parent-resource
  hierarchy.
- Count every enclosing route group toward a maximum nesting depth of two. Middleware
  groups count as a level.
- When another subgroup would exceed that depth, keep its routes flat within the
  second group and retain the remaining relative path and route name explicitly.
- Separate flat logical route families with one blank line. Do not insert blank lines
  between every route in the same family.
- Preserve the final URI, route name, middleware, parameter constraints, and binding
  behavior when refactoring route declarations.
- Do not create a group merely to remove a token from one or two routes.

Example:

```php
Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');

        Route::get('/{project}/flags', [FeatureFlagController::class, 'index'])
            ->whereNumber('project')
            ->name('flags.index');
    });
});
```

### Application actions

- Use focused actions for state-changing or reusable business operations.
- Give an action one primary responsibility and explicit dependencies.
- Return a useful model or result object rather than an ambiguous boolean when callers
  need outcome details.
- Wrap multi-write invariants in a database transaction.
- Record required audit events from the application action inside the same transaction
  as the state change.
- Pass an `Auditable` subject and `AuditEventAction` to
  `RecordAuditEvent::record()` rather than creating `AuditEvent` records directly.

Choose an application action when the operation:

- coordinates more than one model or persistence write;
- owns a transaction, audit event, external side effect, or retry boundary;
- enforces an application workflow used by controllers, commands, or jobs;
- needs injected collaborators; or
- has failure behavior that should remain visible and independently testable.

An action may call model methods for local behavior, but the action retains ownership
of orchestration and transaction boundaries. Do not create an action that merely
renames one obvious Eloquent call without adding a business boundary.

### Models

- Keep relationships, casts, query scopes, and small model invariants on Eloquent
  models.
- Avoid controllers reaching through long relationship chains to implement business
  behavior.
- Avoid hiding security-sensitive or cross-model business operations in observers.
- Protect mass assignment with explicit fillable or guarded decisions.
- Cast lifecycle status, timestamps, booleans, and structured metadata deliberately.
- Cast `AuditEvent.action` to the shared string-backed `AuditEventAction` enum.
- Models that can be audit subjects implement `Auditable` and use
  `HasAuditEvents`. Keep the trait limited to audit identity and the polymorphic
  relationship; do not add a model-level `recordAuditEvent()` write method.
- Use factories that create valid domain objects by default.

Choose a model method when behavior is local to that model or aggregate and can be
understood without request state, service resolution, authorization, or external
effects. Good model methods include derived values, lifecycle predicates, local state
transitions, relationship helpers, and invariant-preserving changes to the model
itself.

Model methods must not:

- coordinate unrelated models or conceal required audit writes;
- begin or commit application transactions;
- resolve dependencies from Laravel's container;
- inspect the authenticated request or make authorization decisions; or
- call external services.

When a local model transition participates in a larger workflow, let an application
action open the transaction and call the model method.

### Audit events

- Define every management audit action in the shared `AuditEventAction` enum. Do not
  repeat dot-separated action strings outside that enum.
- Preserve enum backing values as the stable persistence contract.
- Use `RecordAuditEvent::record()` as the single audit-event creation path.
- Let the owning application action define the transaction boundary and call the
  recorder within it.
- Use the `Auditable` contract for project and subject identity and
  `HasAuditEvents` for the polymorphic relationship.
- Keep audit metadata allowlisted at the call site; never pass entire requests,
  models, credentials, or unfiltered payloads.
- Add tests for the action enum cast, subject relationship, required metadata, and
  rollback when audit persistence fails.

### Dependency injection

- Prefer constructor injection for explicit dependencies.
- Avoid service-locator calls in domain and application code when a dependency can be
  injected.
- Do not add an interface for every class. Add one when multiple implementations,
  external boundaries, or focused testing genuinely require it.

### Configuration

- Read environment variables only from configuration files.
- Access settings through Laravel configuration outside config files.
- Never commit `.env`, production credentials, generated secrets, or real service
  tokens.
- Fail clearly when required production configuration is missing.

## 6. Domain and Security Standards

- Scope every management query to a project authorized for the current user.
- Validate that related project, environment, flag, API key, and audit resources share
  the expected ownership boundary.
- Resolve evaluation project and environment exclusively from the authenticated API
  key record.
- Store only the API-key prefix and one-way secret hash.
- Display the complete key once and redact it everywhere else.
- Use cryptographically secure random generation and constant-time secret comparison.
- Use the same public authentication failure for malformed, unknown, mismatched, and
  revoked evaluation keys.
- Permit multiple active keys per environment for safe rotation.
- Make revocation effective on the next authentication attempt.
- Allowlist audit metadata; never serialize entire requests or models into audit data.
- Keep evaluation traffic out of the management audit log.
- Treat Production state changes, archival, and key revocation as deliberate actions
  requiring explicit UI confirmation.

## 7. API Standards

### Public evaluation API

- Keep public evaluation endpoints under `/api/v1`.
- Follow [API Contract](08-api-contract.md).
- Use stable machine-readable reason and error codes.
- Preserve existing fields within a published API version.
- Add optional fields compatibly; do not silently rename or remove fields.
- Use ISO 8601 UTC strings for date-time values.
- Use JSON consistently and return the documented envelopes.
- Do not accept project or environment selection in evaluation request data.
- Apply endpoint-appropriate rate limiting.

### Management API

- Treat management endpoints as first-party SPA endpoints but keep response shapes
  intentional and tested.
- Return validation errors in a consistent structure consumable by the Vue forms.
- Apply server authorization even when the client hides an action.
- Do not leak the existence of unauthorized resources.

### HTTP semantics

- Use the appropriate method for the operation.
- Make idempotency explicit where retries are likely.
- Use status codes consistently and test important failures.
- Avoid returning HTTP 200 for rejected authentication, authorization, or validation.

### HTTP rate limiting

- Define endpoint-specific limits with `RateLimiter::for()` and attach them through
  named `throttle` route middleware.
- Use `Limit::after()` when only selected response outcomes should consume attempts;
  for login, count authentication `401` responses but not validation failures or
  successful sessions.
- Segment login attempts by normalized email plus IP without storing the plaintext
  email in the cache key.
- Centralize custom limiter names, thresholds, key normalization, inspection, and
  clearing in a focused rate-limit class when multiple call sites must agree.
- Clear accumulated login failures after successful authentication.
- Return intentional JSON `429` responses while preserving Laravel's standard
  rate-limit and retry headers.
- Do not duplicate `tooManyAttempts()` and `hit()` branches in controllers when named
  route middleware can enforce the HTTP boundary.
- Test which responses count, normalization, threshold behavior, successful reset,
  response envelope, and rate-limit headers.

## 8. Database and Migration Standards

- Use plural snake_case table names and singular snake_case foreign keys.
- Use foreign-key constraints for domain relationships.
- Add database uniqueness for project slugs, environment keys, flag keys, environment
  flag pairs, and API-key lookup values as specified by the architecture.
- Add indexes for owner/project lists, evaluation lookup paths, active credential
  lookup, and newest-first audit history.
- Name compound indexes when an explicit name improves portability or maintenance.
- Make migrations reversible where practical; document intentionally irreversible
  data transformations.
- Do not edit an applied migration after it may have been shared; add a new migration.
- Keep migrations deterministic and independent of application services that may
  change later.
- Use explicit transactions for application invariants; do not assume foreign keys
  alone provide business atomicity.
- Seed only deterministic demo and development data. Never seed real credentials.
- Prefer explicit lifecycle state or soft deletion consistently. Do not mix strategies
  casually across related resources.

## 9. Vue and TypeScript Standards

- Use Vue 3 Composition API and `<script setup lang="ts">`.
- Type props, emits, domain models, form input, API payloads, and responses.
- Avoid `any`; document and narrow unavoidable external values.
- Do not mutate props.
- Use computed values for derived state instead of manually synchronized copies.
- Keep page components focused on route-level loading and workflow composition.
- Put typed HTTP operations in service modules.
- Put reusable reactive behavior in composables.
- Keep ordinary form state local.
- Use Pinia only when state genuinely coordinates or persists across routes.
- Do not store sensitive API-key plaintext in persistent browser storage.
- Clear one-time secrets from reactive state when the creation flow ends.
- Treat server responses as authoritative after mutations.
- Disable duplicate submissions while requests are in flight.
- Preserve confirmed state when a mutation fails.

### Vue naming

- Components and component files use PascalCase.
- Reusable primitives use an `App` prefix, such as `AppButton` or `AppDialog`.
- Domain components use specific names, such as `FeatureFlagRow`,
  `EnvironmentState`, or `ApiKeyCreatedDialog`.
- Route pages end in `Page`, such as `ProjectOverviewPage`.
- Composables begin with `use`, such as `useProject` or `useApiRequest`.
- Event names describe completed user intent or state change rather than DOM details.

## 10. Tailwind and CSS Standards

- Use Tailwind CSS as the primary styling system.
- Compose repeated utilities inside reusable Vue components or carefully named CSS
  layers rather than copying long class strings across pages.
- Use semantic variants such as `primary`, `danger`, `enabled`, and `disabled`.
- Keep raw palette choice inside shared tokens or components where practical.
- Avoid arbitrary values when the standard spacing and sizing scale works.
- Do not construct dynamic Tailwind class names that cannot be discovered at build
  time; use explicit mappings.
- Use semantic HTML before ARIA.
- Preserve visible focus styles.
- Never use color as the only indication of state.
- Respect reduced-motion preferences.
- Complete and verify the light theme before spending MVP time on dark mode.
- Avoid `!important` except for a documented integration constraint.

## 11. Accessibility Standards

- All core functionality must be keyboard operable.
- Inputs require persistent labels and associated error messages.
- Buttons require accessible names that describe their action.
- Toggle controls expose their state programmatically.
- Dialogs manage entry focus, focus containment, Escape behavior where appropriate,
  and focus return.
- Tables use meaningful headers and have a readable mobile presentation.
- Async loading and mutation outcomes are announced appropriately.
- Text, controls, and state indicators meet the selected WCAG contrast target.
- Test Production confirmations and one-time API-key behavior without a pointing
  device.

## 12. Testing Standards

### General

- Test observable behavior and important invariants rather than private implementation
  details.
- Use descriptive test names that state the expected behavior.
- Keep tests deterministic and independent of execution order.
- Use factories and focused setup rather than large shared fixtures.
- Avoid real network calls in unit and feature suites.
- A regression fix requires a test that fails without the fix when practical.
- Do not remove or weaken a valid test merely to make a change pass.

### Backend

Use Pest for backend testing.

Cover, where applicable:

- Happy path
- Validation failure
- Unauthenticated and unauthorized access
- Cross-owner, cross-project, and cross-environment isolation
- Missing and archived resources
- Transaction rollback
- Required audit events
- Public response schema and reason codes
- Plaintext-secret absence and redaction
- Credential revocation and rotation
- Rate limiting

Example test descriptions:

```php
it('creates every default environment with a new project');
it('prevents an owner from changing another owners flag');
it('does not store the complete environment key');
it('rolls back flag state when audit recording fails');
```

### Frontend

- Test critical component behavior and form states.
- Prefer user-observable queries and interactions over component internals.
- Cover loading, empty, validation, success, and relevant failure states.
- Cover keyboard behavior for dialogs, menus, toggles, and forms.
- Verify failed mutations retain confirmed state.
- Use Cypress for critical browser and cross-application workflows. Do not replace
  browser behavior with bespoke shell assertions when Cypress can express it clearly.

### Test boundaries

- Unit tests cover isolated domain and evaluation rules.
- Laravel feature tests cover HTTP behavior, authorization, transactions, and
  persistence boundaries.
- Component tests cover reusable Vue interactions.
- Browser tests cover a small number of critical integrated workflows.
- Do not duplicate the same assertion at every level without a clear risk reason.

## 13. Static Analysis and Quality Gates

TF-2 must establish the exact commands and checked-in configuration. At minimum, the
intended gates are:

### Backend

- Laravel Pint
- Pest
- PHPStan at the agreed initial level, increased deliberately rather than bypassed

### Frontend

- TypeScript type checking
- Linting
- Formatting
- Unit or component tests
- Cypress end-to-end tests for critical integrated workflows
- Production build

### Review handoff

Before Code Review, the Developer reports:

- Commands executed
- Pass or failure result
- Checks that could not run and why
- Relevant focused manual verification
- Pull request URL and current head commit
- Required CI checks and their successful results

Do not claim all checks passed when a tool is not installed or configured. Local
checks do not replace required pull-request CI. The Developer owns resolving failures
and confirming the required checks pass again after every substantive review fix.

## 14. Logging and Observability

- Use structured contextual logging where it improves diagnosis.
- Include safe identifiers such as request ID, project ID, environment ID, flag ID,
  and action name when relevant.
- Never include passwords, complete API keys, bearer headers, credential hashes, or
  unfiltered personal attributes.
- Avoid logging ordinary successful evaluations individually in the management log.
- Keep operational logs, append-only management audit events, and future evaluation
  analytics as separate concerns.
- Report unexpected failures to the configured error tracker when one is introduced.

## 15. Git and Change Standards

Follow [Git Branch, Pull Request, and CI Workflow](16-git-branch-pr-ci-workflow.md) as
the single source of truth for branch names, commit messages, staging, pushing, pull
requests, CI, review, merging, and branch cleanup.

All Git work must still preserve unrelated changes, avoid destructive commands, keep
credentials and personal files out of commits, and reference the Jira ticket as
required by that workflow.

## 16. Documentation Standards

- Update documentation in the same change when behavior, API contracts, architecture,
  security decisions, installation steps, or quality commands change.
- Record cross-cutting and difficult-to-reverse decisions as dedicated architecture
  decision documents.
- Keep Jira technical reviews aligned with permanent repository documentation.
- Use Mermaid diagrams when relationships or sequences are materially clearer than
  prose.
- Mark future components explicitly so diagrams do not misrepresent MVP scope.
- Use relative links between repository documents and verify them after changes.
- Do not copy large sections of source documentation into code comments.

## 17. Code Review Standards

Formal code review occurs on the pull request. The reviewer inspects the complete diff
and discussion, leaves line-specific findings as inline comments, and records
cross-cutting findings and the recommendation in the pull-request review summary.
Reviewing only a local diff is preliminary and does not complete the review stage.

Before approval, the reviewer must verify every required CI check passed for the
current head commit. A result from an older commit is not sufficient. Failed,
cancelled, missing, or pending required checks block approval.

Review in this order:

1. Security and data isolation
2. Correctness and regressions
3. API and persistence compatibility
4. Transaction and audit consistency
5. Secret handling
6. Important missing tests
7. Accessibility and failure-state behavior
8. Maintainability that materially affects delivery

Actionable findings require a priority, file and tight line range, concrete failure
scenario, and explanation. Avoid blocking changes for unsupported stylistic preference
when formatter and documented standards permit the code.

The reviewer must assess whether tests adequately cover the ticket's acceptance
criteria, changed behavior, and credible regression paths. Inspect assertions for the
relevant happy paths and important failures. Coverage percentages may support this
assessment when configured, but a percentage neither proves sufficiency nor replaces
behavioral review.

## 18. Exceptions and Evolution

Standards may evolve as the application and tooling become concrete. A proposed
exception must explain:

- The rule being bypassed
- Why compliance is harmful or impossible in this case
- The scope and duration of the exception
- Compensating tests or controls
- Whether the standard or tooling should be updated

Do not create undocumented one-off conventions. Update this document and enforcement
configuration together when the team adopts a new standard.

## 19. Related Documentation

- [Domain and Architecture](07-domain-and-architecture.md)
- [API Contract](08-api-contract.md)
- [Authentication and API Key Decision](11-authentication-and-api-key-decision.md)
- [Frontend Architecture and Design System](12-frontend-architecture-and-design-system.md)
- [Product-to-Delivery Workflow](13-product-to-delivery-workflow.md)
- [Git Branch, Pull Request, and CI Workflow](16-git-branch-pr-ci-workflow.md)
