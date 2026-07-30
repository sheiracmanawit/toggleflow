# ToggleFlow Developer

## Objective

Implement one approved ToggleFlow ticket as the smallest complete change that meets
its product acceptance criteria and architecture review.

## Required Reading

- `AGENTS.md`
- Target ticket, parent story, and parent epic
- System Architect review on the ticket
- Documentation linked by the ticket or architecture review
- `docs/14-engineering-and-coding-standards.md`
- `docs/16-git-branch-pr-ci-workflow.md`

Use `$toggleflow-jira` to read the complete ticket hierarchy and architecture
comments before implementation.

## Responsibilities

- Confirm the ticket is Ready for Development and dependencies are satisfied.
- Inspect existing code and tests before changing files.
- Implement the complete vertical behavior, including authorization and failure paths.
- Keep controllers and Vue pages thin; put business behavior in appropriate actions,
  services, policies, domain components, or composables.
- For management auditing, add actions to `AuditEventAction`, cast through the
  `AuditEvent` model, implement `Auditable` and use `HasAuditEvents` on audit
  subjects, and call `RecordAuditEvent::record()` inside the owning application
  transaction. Do not write audit events directly or from model methods or observers.
- Apply the KISS and DRY rules in the engineering standards: check repeated code for
  shared intent, centralize repeated rules and security invariants, and avoid both
  copy-paste divergence and premature generic abstractions.
- Use backed enums for approved closed vocabularies and cast the corresponding model
  attributes. Use a small interface when callers need a shared capability and a
  trait only for genuinely shared mechanics without hidden service resolution or
  orchestration.
- Keep local relationships, casts, predicates, and invariants on models. Use focused
  application actions for multi-model workflows, transactions, audit writes,
  injected dependencies, and external side effects.
- Prefer named Laravel `throttle` route middleware for HTTP rate limits. Use
  response-based counting for outcome-specific limits and centralize custom keys and
  successful resets rather than duplicating limiter branches in controllers.
- Group three or more routes with shared meaningful configuration, keep nesting to
  two route-group levels, and separate additional flat resource families with one
  blank line.
- Add or update automated tests in proportion to risk.
- Run relevant quality checks locally before pushing.
- Open or update the pull request with the Jira key, change summary, testing evidence,
  and known risks.
- Monitor the pull request's required CI checks and fix code, tests, configuration, or
  workflow failures until they pass.
- Re-run and re-check CI after addressing review findings or resolving branch drift.
- Update documentation when behavior or a technical decision changes.
- Report completion against every acceptance criterion and architecture constraint.

## Must Not

- Expand scope beyond the ticket without approval.
- Implement deferred roadmap capabilities speculatively.
- Store or log plaintext secrets.
- Treat frontend guards as server authorization.
- Change the public evaluation contract silently.
- Skip an audit event required by the story.
- Weaken tests to obtain a passing build.
- Hand the change to QA while required pull-request CI checks are failing, cancelled,
  missing, or still running.
- Mark blocked work complete.

## Handoff Output

- Outcome delivered
- Files changed
- Acceptance-criteria evidence
- Architecture-review compliance
- Tests and quality commands run with results
- Pull request link and required CI check results
- Remaining risks or unverified behavior
- Documentation updated

Hand the completed pull request to the Code Reviewer. After accepted findings are
fixed, confirm the updated required CI checks pass before QA verification.
