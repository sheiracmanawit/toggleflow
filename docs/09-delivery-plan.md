# One-week Delivery Plan

## 1. Objective

Deliver a polished MVP 0.1 that demonstrates ToggleFlow's complete core loop and can
serve as the foundation for continued product development.

The plan assumes one primary developer. Tasks are ordered by dependency and portfolio
value. A day is complete only when its behavior is tested and integrated; unfinished
features should not accumulate across the week.

## 2. Delivery Principles

- Complete one vertical workflow before adding secondary capabilities.
- Keep controllers thin and authorization centralized.
- Prefer correct database-backed evaluation before adding Redis.
- Do not implement speculative rule-engine or organization tables.
- Preserve time for documentation, demo data, visual polish, and testing.
- Cut optional presentation features before cutting security or evaluation tests.

## 3. Day-by-day Plan

### Day 1 — Foundation and Domain

- Establish the monorepo workspaces: Laravel in `apps/platform-api` and the Vue 3 SPA
  in `apps/dashboard`.
- Configure MySQL, environment variables, formatting, Pest, and static analysis.
- Implement authentication using Sanctum.
- Add project, environment, feature flag, and environment-flag migrations and models.
- Add ownership policies and model factories.
- Automatically create Development, Staging, and Production environments.
- Test project isolation and database constraints.

**Exit condition:** a user can sign in, create a project, and see its environments.

### Day 2 — Feature Management

- Implement flag listing, creation, editing, and archival.
- Create environment states disabled by default.
- Implement per-environment enable and disable actions transactionally.
- Add validation, authorization, and feature tests.
- Build the main project and flag UI with useful empty and error states.

**Exit condition:** boolean flags can be safely managed across all environments.

### Day 3 — Evaluation API and Keys

- Implement secure environment API-key issuance, one-time display, and revocation.
- Implement dedicated API-key middleware or guard.
- Implement the evaluation action and response resource.
- Add rate limiting and stable error responses.
- Test invalid, revoked, and cross-environment credentials.
- Add a small cURL-based integration example.

**Exit condition:** an external client can evaluate a flag and immediately observe a
dashboard state change.

### Day 4 — Auditability and Dashboard

- Implement append-only audit events for required management actions.
- Ensure state change and audit event share a database transaction.
- Build recent-activity and summary dashboard components.
- Add project and environment context to navigation.
- Review responsive behavior and accessible labels, focus, and contrast.

**Exit condition:** the product communicates current state and the history of changes.

### Day 5 — Hardening and Portfolio Polish

- Complete missing unit and feature tests.
- Run formatting, static analysis, and the full test suite.
- Add deterministic demo seed data and a documented demo account.
- Finish installation and configuration documentation.
- Add architecture decisions, screenshots, and an evaluation walkthrough to README.
- Verify the project from a clean installation.
- Record or rehearse a concise demo using the primary demo story.

**Exit condition:** another developer can install, understand, and demonstrate the
project without private instructions.

### Weekend or Buffer

Use remaining time for defects and presentation quality. Optional enhancements are,
in order:

1. Project-level flag filtering.
2. API-key last-used timestamp.
3. Dark mode.
4. Docker packaging.
5. Additional Cypress coverage beyond the critical foundation and demo flows.

Do not begin percentage rollouts during the buffer unless every MVP acceptance
criterion is already satisfied.

## 4. Priority Tiers

### Must Ship

- Login/logout or reliable seeded access
- Project ownership and environments
- Boolean flag lifecycle and environment state
- Secure environment API keys
- Public evaluation endpoint
- Required audit events
- Automated tests for the critical path and security boundaries
- Installation guide and demo data

### Should Ship

- Summary dashboard
- Responsive, accessible core screens
- Key last-used tracking
- Clear archive and empty-state experiences
- Static analysis in CI

### Could Ship

- Search or filtering
- Dark mode
- Docker setup
- Additional charts
- Browser end-to-end testing

### Will Not Ship in MVP 0.1

- Percentage rollout or targeting rules
- Organizations and RBAC
- SDK packages
- Notifications, scheduling, analytics, and experiments

## 5. Risk Register

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Authentication scaffolding consumes too much time | Core workflow starts late | Use established Laravel/Sanctum patterns and keep registration/reset optional. |
| UI polish begins before the workflow works | Attractive but incomplete demo | Finish and test each backend vertical slice before visual refinement. |
| Rule-engine design expands scope | Evaluation API misses deadline | Implement a small evaluator interface with one static boolean strategy. |
| API keys are stored or logged unsafely | Serious security flaw | Store hashes, show once, redact logs, and test persistence. |
| Audit data diverges from state | Demo and reliability failure | Write state and audit event in one transaction. |
| Redis or Docker causes environment delays | Lost delivery time | Keep the MVP database-backed; add infrastructure only after acceptance criteria pass. |
| Documentation is postponed | Portfolio is difficult to assess | Update docs alongside features and reserve the final day for clean-install verification. |
| Repository migration consumes feature time | Core workflow starts late | Treat the move as a mechanical, tested foundation change; do not extract services or redesign domain behavior during it. |

## 6. Scope-cut Order

If the schedule slips, remove work in this order:

1. Charts and decorative dashboard statistics.
2. Dark mode and non-critical animation.
3. Search and convenience filtering.
4. API-key last-used tracking.
5. Public registration and password reset; retain seeded access.
6. Project editing; retain creation and viewing.

Do not cut ownership authorization, secure key storage, environment isolation,
evaluation correctness, state-change audit events, or critical-path tests.

## 7. Demonstration Script

1. Sign in using the demo account.
2. Open the seeded Checkout Service project.
3. Show the three environments and the disabled `new-checkout` flag.
4. Evaluate the Production flag with cURL and receive `false`.
5. Enable the flag in Development and show that Production remains `false`.
6. Enable the flag in Production and evaluate again to receive `true`.
7. Disable it to demonstrate instant rollback.
8. Open the audit log and show both Production changes with actor and timestamps.
9. Revoke the API key and show that evaluation is rejected.

This sequence demonstrates product value, isolation, security, and traceability in a
few minutes.

## 8. Post-MVP Continuation

After tagging MVP 0.1, development continues incrementally:

1. Add deterministic percentage rollout and targeting context.
2. Add ordered rules and reusable segments.
3. Publish OpenAPI documentation and the first server-side SDKs.
4. Introduce organizations, migrations for existing owners, and role permissions.
5. Add caching, webhooks, and production operations guidance.
6. Build scheduling, experiments, and analytics only after the evaluation platform is
   stable.

Every release should preserve the public evaluator contract or introduce a new API
version with a documented migration path.
