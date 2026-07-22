---
name: toggleflow-implement-ticket
description: Implement an approved ToggleFlow Jira ticket as a complete vertical change with tests, quality checks, documentation, and acceptance-criteria evidence. Use when Codex is asked to build, implement, fix, or complete a TF Jira issue in the ToggleFlow repository after Product Owner definition and System Architect review, including backend, Vue, API, database, security, or integrated feature work.
---

# Implement a ToggleFlow Ticket

Implement one approved Jira ticket without expanding MVP scope. Treat the ticket's
Product Owner content as the definition of value and the System Architect review as
technical guardrails.

## Required Context

Before editing code:

1. Read repository `AGENTS.md` completely.
2. Read `.agents/developer.md` completely.
3. Read `docs/14-engineering-and-coding-standards.md` completely.
4. Fetch the target Jira ticket, parent story, parent epic, comments, and subtasks.
5. Read every repository document linked by the ticket or architecture review.
6. Inspect the current worktree, relevant implementation, tests, and configuration.

Use the Atlassian connector for Jira reads when available. If the ticket cannot be
retrieved, stop and request its contents or restored access rather than guessing.

## Readiness Gate

Proceed only when:

- The ticket has a clear outcome or task completion condition.
- Acceptance criteria are testable.
- Required Product Owner decisions are resolved.
- A System Architect review exists when required by `AGENTS.md`.
- Blocking dependencies are complete or available in the worktree.

If readiness is incomplete, report the exact missing item. Do not invent product
behavior or architecture to unblock implementation.

## Workflow

1. Restate the bounded outcome and identify affected layers.
2. Map every acceptance criterion and architect requirement to implementation and
   verification work.
3. Inspect existing patterns before adding new abstractions.
4. Implement the smallest complete vertical behavior.
5. Enforce authorization, validation, isolation, failure behavior, transactions,
   auditing, and secret handling required by the change.
6. Add tests for the happy path and important negative paths.
7. Run focused checks during iteration, then all relevant ticket-level gates.
8. Review the diff for unrelated changes, leaked secrets, debug code, and accidental
   contract changes.
9. Update permanent documentation when delivered behavior, commands, architecture,
   or API contracts change.
10. Open or update the pull request when the user has authorized the Git and remote
    workflow, then monitor required CI checks and fix failures until they pass.
11. Report evidence against every acceptance criterion and architecture constraint.

## ToggleFlow Guardrails

- Keep management and evaluation authentication separate.
- Use Sanctum for the first-party SPA and opaque environment keys for evaluation.
- Resolve evaluation project and environment only from the credential.
- Never persist, log, serialize, or render complete API keys after creation.
- Scope management behavior through Laravel policies.
- Commit state changes and required audit events atomically.
- Preserve `/api/v1` response contracts.
- Treat MySQL as authoritative; do not add Redis for correctness.
- Use Vue 3 TypeScript and Tailwind without a large component framework.
- Require deliberate UI confirmation for Production changes, revocation, and archival.
- Keep post-MVP capabilities out of the change unless the approved ticket moves them
  into scope.

## Change Discipline

- Preserve unrelated user changes in a dirty worktree.
- Do not use destructive Git commands.
- Do not create a branch, commit, push, transition Jira, or mark Jira complete unless
  the user requests that external or Git action.
- Do not weaken a test or quality rule merely to obtain a pass.
- Do not combine unrelated cleanup with the ticket.
- If implementation reveals a product decision, stop that portion and route the
  question to the Product Owner.
- If implementation reveals a cross-cutting technical decision, route it to the System
  Architect and update permanent documentation after approval.

## Verification

Run the checks relevant to the implemented layers. Use the actual commands configured
by TF-2 and documented in the repository. Expected categories include:

- Pest
- Laravel Pint
- PHPStan
- TypeScript type checking
- Frontend linting and formatting
- Frontend unit or component tests
- Production frontend build
- Cypress end-to-end tests when configured and relevant

Do not claim a check passed when its tool is unavailable. Report it as not run with
the concrete reason and remaining risk.

Local checks provide fast feedback but do not replace the pull-request gate. Before
handoff, confirm every required CI check passed for the current pull-request head
commit. If creating or accessing the pull request is not authorized or available,
report the handoff as incomplete rather than claiming CI success.

## Handoff Format

Return:

```markdown
## Outcome

## Files changed

## Acceptance criteria
| Criterion | Evidence | Result |
| --- | --- | --- |

## Architecture review compliance

## Tests and quality checks
| Command or check | Result |
| --- | --- |

## Pull request and CI
- Pull request: ...
- Head commit: ...
- Required checks: Passed/Failed/Pending/Unavailable

## Documentation

## Remaining risks or unverified behavior
```

Recommend independent review with `$toggleflow-review-change` after implementation.
