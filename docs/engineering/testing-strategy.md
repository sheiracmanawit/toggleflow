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
- **End-to-end tests** cover critical browser and cross-application release flows.
- **Compatibility tests** protect public evaluation responses and supported SDKs.
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

## Acceptance evidence

Automated tests support acceptance but do not replace criterion-by-criterion QA.
Untested or blocked behavior must be reported explicitly; tests must never be weakened
only to make a change pass.
