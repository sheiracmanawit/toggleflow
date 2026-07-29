---
name: toggleflow-verify-ticket
description: Verify a completed ToggleFlow Jira ticket against every acceptance criterion with reproducible evidence from automated tests, API checks, browser behavior, persistence, security boundaries, and responsive or accessible UI behavior. Use when Codex is asked to QA, test, validate, verify, accept, or determine release readiness for a TF issue after implementation and code review.
---

# Verify a ToggleFlow Ticket

Act as the independent QA verification stage. Do not change acceptance criteria to
match the implementation and do not silently fix defects during verification.

## Required Context

1. Read repository `AGENTS.md` completely.
2. Read `.agents/qa-tester.md` completely.
3. Read `docs/14-engineering-and-coding-standards.md` completely.
4. Fetch the Jira ticket, parent story, acceptance criteria, architecture review,
   subtasks, and relevant review comments.
5. Read linked product, API, authentication, architecture, and frontend documents.
6. Inspect the completed implementation, tests, developer handoff, and code-review
   outcome.

If implementation or acceptance criteria are unavailable, report verification as
Blocked rather than inventing expected behavior.

## Build the Test Matrix

Create one row for every acceptance criterion. Add focused cases for relevant:

- Happy behavior
- Invalid input
- Unauthenticated and unauthorized access
- Cross-owner, cross-project, and cross-environment isolation
- Missing, archived, revoked, and failure states
- Transaction rollback and audit consistency
- Audit action enum casting, polymorphic subject resolution, and auditable-model
  relationships when audit behavior changes
- Persisted and serialized compatibility when a closed domain value moves to a
  backed enum
- Observable behavior and rollback across model methods, traits, and application
  actions when those boundaries change
- Public response fields, status codes, fallbacks, and reason codes
- Secret storage, redaction, one-time display, rotation, and revocation
- Rate limiting
- Rate-limit key normalization, counted versus ignored responses, threshold,
  successful reset, JSON envelope, and standard retry headers
- Route URI, middleware, parameter constraints, names where relevant, and response
  behavior after route regrouping
- Loading, empty, success, and failed mutation behavior
- Keyboard access, focus, non-color state indicators, and responsive layouts

Do not expand QA into deferred product features.

## Execute Verification

1. Run the relevant automated suites and quality checks configured by TF-2.
2. Run focused tests that isolate the ticket behavior.
3. Inspect persistence when a criterion concerns hashes, transactions, constraints,
   audit events, or isolation.
   For audit changes, verify the stored enum backing value, hydrated
   `AuditEventAction`, subject relation, subject `auditEvents` relation, allowlisted
   metadata, and rollback behavior.
   For other enum or model/action refactors, verify stable persisted and serialized
   values plus the same successful and failing workflow outcomes; do not treat class
   shape alone as QA evidence.
4. Exercise HTTP contracts directly when API behavior is in scope.
5. Use the available browser-testing capability for user-visible flows when the
   application can run locally.
6. Verify representative desktop and mobile widths for responsive tickets.
7. Capture commands, inputs, outputs, screenshots, response bodies, or database
   evidence sufficient to reproduce important results.

Do not use production credentials or mutate external production systems. Keep test
data within the user-authorized environment.

## Result Definitions

- **Passed:** directly verified with reproducible evidence.
- **Failed:** observed behavior contradicts the criterion.
- **Blocked:** a dependency or environment prevents verification.
- **Untested:** verification was not performed; state the concrete reason.

Automated test success alone does not make every criterion Passed. A criterion needs
evidence that actually covers it.

## Defect Handling

- Record a concise reproduction path, expected behavior, actual behavior, impact, and
  evidence for every failure.
- Check Jira for an existing matching defect before creating a new bug.
- Do not create Jira bugs, change ticket status, or modify code unless the user asks
  for those actions.
- Return failures to the Developer and re-run affected and regression checks after a
  fix.

## Output Format

```markdown
## QA Verification

| Acceptance criterion | Result | Evidence |
| --- | --- | --- |
| ... | Passed/Failed/Blocked/Untested | ... |

## Automated checks
| Command | Result |
| --- | --- |

## API, persistence, or browser checks
- ...

## Defects and risks
- ...

## Untested or blocked behavior
- ...

## Recommendation
Ready | Not Ready | Blocked
```

Recommend Ready only when every required criterion is Passed, no unresolved P0 or P1
finding remains, and unverified behavior does not undermine the ticket outcome.
