---
name: toggleflow-review-change
description: Independently review ToggleFlow code changes against their Jira ticket, System Architect review, architecture, security invariants, API contracts, coding standards, and tests. Use when Codex is asked to review a diff, branch, pull request, completed TF ticket, implementation, or proposed fix for correctness, regressions, authorization, environment isolation, secret exposure, audit consistency, frontend safety, accessibility, or missing tests.
---

# Review a ToggleFlow Change

Perform an independent, findings-first review. Do not modify code during the initial
review unless the user explicitly asks for fixes after receiving findings.

## Required Context

1. Read repository `AGENTS.md` completely.
2. Read `.agents/code-reviewer.md` completely.
3. Read `docs/14-engineering-and-coding-standards.md` completely.
4. Fetch the Jira ticket, parent story, acceptance criteria, architecture review, and
   relevant subtasks when a TF issue is identified.
5. Read documents linked by the ticket and architecture review.
6. Open the pull request and inspect its description, current head commit, commits,
   complete diff, affected files, discussion, tests, and configuration.
7. Inspect every required CI check for the current head commit. Read failed-check logs
   far enough to distinguish change failures from infrastructure failures.

Use `$toggleflow-jira` for the target, parents, comments, and subtasks. If Jira
context cannot be retrieved, identify the concrete failure and do not infer ticket
requirements from the implementation.

If there is no accessible pull request, explain what evidence is missing and do not
claim to have completed the Code Reviewer stage. A local diff can support preliminary
feedback, but formal review still requires the pull request as its source.

## Review Order

Review in this priority order:

1. Security and data isolation
2. Correctness and regressions
3. Public API and persistence compatibility
4. Transaction and audit consistency
5. Secret handling
6. Important missing tests
7. Accessibility and failure-state behavior
8. Maintainability that materially affects correctness or delivery

## ToggleFlow Risk Checklist

Check where relevant:

- Every management query is scoped to the authenticated owner's project.
- Related project, environment, flag, key, and event records cannot be mixed across
  ownership boundaries.
- Evaluation derives project and environment only from the API key.
- Malformed, unknown, mismatched, and revoked keys do not leak existence information.
- Complete API keys, hashes, passwords, and bearer headers are absent from storage,
  logs, audit metadata, errors, and later UI responses.
- Flag state and required audit events commit or roll back together.
- Audit names use the shared `AuditEventAction` enum; `AuditEvent.action` is enum
  cast; subjects implement `Auditable` and use `HasAuditEvents`; and application
  actions call `RecordAuditEvent::record()` within their transaction. Flag direct
  `AuditEvent` creation and model- or observer-hidden audit writes.
- Archived projects and flags follow documented evaluation and listing behavior.
- `/api/v1` response fields, reason codes, status codes, and fallbacks remain stable.
- Rate limiting is present on sensitive authentication and evaluation paths.
- Failed Vue mutations retain confirmed server state.
- Production actions, key revocation, and archival require deliberate confirmation.
- State is not communicated through color alone and keyboard behavior remains usable.
- Tests cover important negative paths rather than only happy behavior.
- No deferred roadmap capability or unnecessary abstraction entered the MVP.
- Closed vocabularies use a single backed enum and relevant model cast; open,
  user-defined, or third-party values are not artificially constrained by enums.
- Interfaces describe capabilities required by callers, and traits reuse narrow
  mechanics without hiding dependencies, authorization, transactions, or side
  effects.
- Models own local relationships and invariants; application actions own multi-model
  workflows, transactions, audit writes, injected collaborators, and external
  effects.
- Named HTTP limiters are attached through route middleware, count the intended
  responses, use non-sensitive normalized keys, preserve rate-limit headers, and
  clear successful login failures when required.
- Route declarations follow the three-route grouping threshold and two-level nesting
  cap, visually separate remaining families, and preserve URI, name, middleware,
  constraint, and binding contracts.

## Evidence Standard

An actionable finding must describe a concrete failure or credible regression path.
Trace input, state, and execution far enough to show why the issue occurs. Read the
relevant test before asserting that coverage is missing.

Assess coverage by mapping acceptance criteria and changed risk paths to actual test
assertions. Use a configured line or branch coverage report as supporting evidence
when available, but do not require or trust a percentage without examining what
behavior is covered. Missing tests for a credible regression path are actionable even
when aggregate coverage is high.

## Pull Request and CI Gate

- Review the actual pull request, including its complete diff and discussion.
- Emit line-specific actionable findings as Codex inline code-review comments in the
  current task.
- Put cross-cutting observations, acceptance gaps, test-coverage assessment, CI
  status, residual risks, and the recommendation in the normal response.
- Verify required checks belong to the current head commit, not an older revision.
- Do not approve while a required check is failing, cancelled, missing, or pending.
- After fixes, review the updated diff and confirm required CI passes before approval.

Do not report:

- Pure stylistic preference allowed by checked-in tooling and standards
- Hypothetical issues without a triggering path
- Pre-existing unrelated defects unless the change materially worsens them
- A missing future feature excluded by the ticket
- Duplicate findings for the same root cause

## Finding Priority

- **P0:** catastrophic or immediately exploitable; release must stop.
- **P1:** serious security, data, authorization, or core correctness failure.
- **P2:** meaningful functional regression, contract issue, or important test gap.
- **P3:** localized maintainability or low-impact correctness issue worth addressing.

## Output Format

Lead with findings ordered by priority. Emit one directive for each actionable
finding:

```text
::code-comment{title="[P1] Short issue title" body="Concrete failure, triggering scenario, impact, recommended fix, and relevant missing test." file="/absolute/path/File.php" start=268 end=274 priority=1}
```

Use an absolute path, an exact tight line range, and the matching numeric priority
from `0` through `3`. Attach findings only to meaningful source lines. Keep general
observations in the normal response.

After findings, include:

- Pull request URL and reviewed head commit
- Required CI checks and their results
- Test-coverage assessment, including important covered and uncovered behavior
- Acceptance criteria not demonstrated by the change
- Tests and checks inspected or run
- Residual risks and unverified behavior

If there are no actionable findings, say so explicitly and still report meaningful
coverage gaps or residual risks.

Codex inline comments exist only in the current Codex task. They do not post to
GitHub, modify the annotated file, or submit a GitHub review. Post GitHub comments
only when the user separately requests that external action. Do not merge, transition
Jira, or implement fixes unless the user separately authorizes those actions.
