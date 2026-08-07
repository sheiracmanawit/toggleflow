# Testing Strategy

## Purpose

Testing provides evidence that ToggleFlow preserves release correctness, security,
compatibility, accessibility, and recoverability as capabilities evolve.

## Test layers

- **Unit tests** cover domain rules, validation, deterministic allocation, and small
  failure cases.
- **Backend feature tests** cover authorization, policies, persistence, transactions,
  audit behavior, rate limits, and API contracts.
- **Frontend unit and component tests** cover workflow state, validation, failure
  recovery, accessible names, and reusable product components.
- **UI-foundation tests** cover ToggleFlow's Nuxt UI configuration, semantic token
  mappings, provider composition, non-color state labels, accessible icon names,
  validation associations, overlays, and production exclusion. They do not retest
  Nuxt UI internals.
- **Theme-preference tests** cover valid and invalid persisted values, System
  resolution and live media-query changes, listener cleanup, storage failure,
  pre-bootstrap application, root class/color-scheme updates, and the selector's
  accessible choice state.
- **Theme contrast tests** verify the teal/mint primary family in both presentations,
  solid action and focus contrast, and visual separation from emerald Enabled and
  Production-only violet.
- **End-to-end tests** cover critical browser and cross-application release flows.
- **Compatibility tests** protect public evaluation responses and supported SDKs.
- **Architecture tests** in `apps/platform-api/tests/Architecture` enforce backend
  module dependency direction, Core purity, service-provider and route ownership,
  and removal of legacy layer-first application directories.
- **Frontend architecture checks** in `apps/dashboard/scripts/check-boundaries.mjs`
  enforce app/feature/shared direction, public cross-feature entry points, and an
  acyclic feature graph. Allowed and prohibited fixtures execute under `pnpm lint`.
- **Operational exercises** validate clean installation, upgrade, backup, restore,
  and recovery before corresponding product claims are made.

## Required risk coverage

Changes affecting authorization, credentials, evaluation, multi-tenancy, rollout
rules, audit transactions, or Production controls require happy-path and important
failure-path evidence. Cross-project and cross-environment isolation must be tested
explicitly.

Percentage Rollouts require deterministic distribution tests. User Targeting and
Targeting Rules require rule-order, missing-context, privacy, and fallback tests.

## Quality checks

Run the checks relevant to the change: Pest, Laravel Pint, PHPStan, frontend type
checking, linting, formatting, unit or component tests, and Cypress for critical
flows. Required pull-request checks must pass for the current head commit before QA
handoff.

Backend architecture rules run with Pest as part of the normal backend suite. New
cross-module dependencies must be represented by a narrow public contract or domain
type and reflected in the architecture tests. Feature and unit tests still prove
runtime behavior; architecture tests prevent prohibited dependency direction and
implementation coupling before those violations become runtime regressions.

## Acceptance evidence

Automated tests support acceptance but do not replace criterion-by-criterion QA.
Untested or blocked behavior must be reported explicitly; tests must never be weakened
only to make a change pass.
