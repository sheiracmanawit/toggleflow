# ToggleFlow Agent Instructions

## Mission

Deliver ToggleFlow MVP 0.1 as a polished, secure, self-hosted feature-flag platform
without compromising the documented path to later releases.

## Required Context

Before changing product behavior, read the relevant Jira ticket and the documents it
references. Use these documents as the default source of truth:

- `docs/06-mvp-product-requirements.md`
- `docs/07-domain-and-architecture.md`
- `docs/08-api-contract.md`
- `docs/09-delivery-plan.md`
- `docs/10-architecture-and-flow-diagrams.md`
- `docs/11-authentication-and-api-key-decision.md`
- `docs/12-frontend-architecture-and-design-system.md`
- `docs/14-engineering-and-coding-standards.md`
- `docs/16-git-branch-pr-ci-workflow.md`

When documents conflict, do not silently choose one. Identify the conflict and route
the decision to the Product Owner for product intent or the System Architect for
technical direction.

## MVP Boundaries

MVP 0.1 includes authentication, directly owned projects, default environments,
boolean feature flags, environment-specific state, opaque environment API keys, a
versioned evaluation API, audit history, and a focused Vue dashboard.

Do not implement percentage rollouts, targeting, organizations, team RBAC, published
SDKs, scheduling, notifications, experiments, analytics, or SSR unless the approved
ticket and documentation explicitly move that capability into scope.

## Architecture Invariants

- Use one monorepo with the Laravel modular monolith in `apps/platform-api` and the
  Vue 3 TypeScript SPA in `apps/dashboard`. Do not add application code at the
  repository root.
- Keep the MVP evaluation API inside `apps/platform-api`; do not create a separate
  runtime service without an approved architecture decision based on measured need.
- Use Sanctum cookie-based authentication for the first-party dashboard.
- Use opaque, hashed API keys scoped to one environment for evaluation.
- Do not use Passport, JWTs, or Sanctum personal tokens for MVP evaluation.
- Resolve project and environment from the evaluation credential; request data cannot
  override either value.
- Keep management and evaluation controllers, authentication, and response contracts
  separate.
- Treat MySQL as authoritative. Redis is a future cache, not required for correctness.
- Store environment-specific state separately from flag metadata.
- Commit release-state changes and their audit events in one transaction.
- Define management audit action names in the shared string-backed
  `AuditEventAction` enum and cast `AuditEvent.action` to that enum.
- Make Eloquent audit subjects implement `Auditable` and use `HasAuditEvents`.
  Record events through `RecordAuditEvent` from the owning application action; do
  not hide audit writes behind model methods or observers.
- Never persist or log plaintext API keys, passwords, bearer headers, or unfiltered
  sensitive request data.
- Centralize authorization in Laravel policies. Client-side guards are not an
  authorization boundary.
- Archive lifecycle resources instead of permanently deleting them through ordinary
  MVP behavior.

## API Invariants

- Keep public evaluation endpoints under `/api/v1`.
- Preserve the response and error envelopes in `docs/08-api-contract.md`.
- Return safe boolean fallbacks and documented reasons for missing, archived, or
  unconfigured flags.
- Authentication errors must not reveal whether a key prefix, project, environment,
  or flag exists.
- Apply named Laravel route limiters to authentication and evaluation requests.
  Use response-based counting when only specific outcomes should consume attempts,
  and centralize custom key normalization and successful-reset behavior.
- Client applications remain responsible for a local fallback when ToggleFlow cannot
  be reached.

## Frontend Invariants

- Use Vue 3 Composition API, TypeScript, Vite, Vue Router, and Tailwind CSS.
- Use Pinia only for state that genuinely spans routes.
- Build reusable UI primitives and domain components without introducing a large
  component framework.
- Treat Disabled as a normal state; reserve danger styling for failures and
  destructive actions.
- Require explicit confirmation for Production state changes, key revocation, and
  archival.
- Never communicate state through color alone.
- Handle loading, empty, validation, success, and relevant failure states.
- Preserve the last confirmed server state after a failed mutation.
- Verify keyboard access, focus behavior, responsive layouts, and accessible labels.

## Delivery Workflow

1. The Product Owner writes and splits the user story and owns value, acceptance
   criteria, exclusions, priority, and product questions.
2. The System Architect reviews product-ready work and adds technical design, security
   and data considerations, implementation tasks, test considerations, dependencies,
   and documentation references.
3. The Developer implements only work that is ready, opens or updates the pull
   request, and remains responsible for fixing failures until all required CI checks
   pass.
4. The Code Reviewer reviews the actual change from its pull request, verifies the
   required CI checks passed, assesses whether the tests adequately cover the changed
   behavior and important risks, and records actionable feedback on the pull request.
5. The QA Tester verifies every acceptance criterion and records Passed, Failed,
   Blocked, or Untested with evidence.
6. The Developer addresses accepted findings before completion.

Architecture review may be lightweight for low-risk presentation work. It is required
for authentication, authorization, persistence, API contracts, credentials,
evaluation, caching, audit behavior, multi-tenancy, and rollout rules.

## Role Definitions

Read the appropriate role instructions before performing that role:

- `.agents/product-owner.md`
- `.agents/system-architect.md`
- `.agents/developer.md`
- `.agents/code-reviewer.md`
- `.agents/qa-tester.md`

One agent may perform multiple roles sequentially for small work, but must preserve
the role boundaries. Do not present an implementation self-review as independent.

## Project Skills

Use the repository skills in `.agents/skills` for repeatable delivery stages:

- `$toggleflow-implement-ticket` implements an approved, architecture-reviewed Jira
  ticket with tests and acceptance evidence.
- `$toggleflow-review-change` independently reviews a completed change and reports
  findings without modifying code.
- `$toggleflow-verify-ticket` verifies every acceptance criterion with reproducible QA
  evidence.

Use `$write-and-split-user-stories` for Product Owner story writing and splitting.
Keep implementation, independent review, and QA verification as separate passes for
security-sensitive or high-risk tickets.

## Jira Rules

- Work from the ToggleFlow project `TF`.
- Preserve the parent epic and story hierarchy.
- User-facing value belongs in Stories; implementation layers belong in Tasks or
  Subtasks.
- Do not change product intent silently during architecture review.
- Put unresolved product decisions back to the Product Owner.
- Put architectural decisions with cross-cutting impact in repository documentation,
  not only in a Jira comment.
- Do not transition a ticket or declare it complete without evidence appropriate to
  the workflow stage.

## Quality Expectations

When the application infrastructure exists, run the checks relevant to the change:

- Pest
- Laravel Pint
- PHPStan
- Frontend type checking
- Frontend linting and formatting
- Frontend unit or component tests
- Cypress end-to-end tests for critical browser and cross-application flows

The Developer must confirm the required pull-request CI checks pass before handing a
change to QA. A local pass does not replace required CI evidence. The Code Reviewer
must inspect the pull-request checks and must not approve while required CI is failing,
cancelled, missing, or still running.

Add tests for happy paths and important failures, especially authorization,
cross-project and cross-environment isolation, secret handling, revocation, audit
transactions, response compatibility, and Production-change UI behavior.

Do not weaken or delete a test merely to make a change pass. Explain checks that could
not run and what remains unverified.

## Coding Standards

Follow `docs/14-engineering-and-coding-standards.md` for PHP, Laravel, Vue,
TypeScript, Tailwind, API, database, testing, security, Git, and documentation
conventions. When the implementation introduces formatter, linter, type-checker, or
static-analysis configuration, treat the checked-in configuration as executable
enforcement of that document.

Apply its design-selection rules consistently: backed enums represent closed domain
vocabularies; small interfaces define capabilities needed by callers; traits reuse
narrow mechanics without hidden orchestration; models own local behavior; and
application actions own workflows, transactions, injected collaborators, audit
writes, and external side effects.

Group three or more routes when they share meaningful middleware, URI or name
prefixes, constraints, or resource hierarchy. Keep route-group nesting to two levels;
within the second group, leave additional route families flat and separate them with
a blank line.

Do not introduce a conflicting local convention silently. Propose material standards
changes through documentation review and update all affected tooling in the same
change.

## Completion Standard

Work is complete only when its acceptance criteria are satisfied, relevant
architecture constraints are honored, important failure paths are tested, required
quality checks pass, and affected documentation remains accurate.
