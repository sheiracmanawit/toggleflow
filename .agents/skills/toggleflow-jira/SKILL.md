---
name: toggleflow-jira
description: Read, search, comment on, and update ToggleFlow Jira issues through the repository-local Jira REST helper. Use when Codex needs complete TF ticket context, including descriptions, comments, parents, subtasks, links, status, or metadata, or when the user explicitly authorizes a Jira write such as adding a comment, editing a description, creating or linking an issue, or transitioning workflow state.
---

# ToggleFlow Jira

Use the repository-local `scripts/jira` helper from the repository root. Treat Jira
as the source of ticket state and `AGENTS.md` as the source of project workflow rules.

When a ticket changes product behavior, compare it with
`docs/04-product-capabilities.md`. Available, Committed, Planned, and Exploring
describe product maturity; none of those statuses substitutes for an approved,
delivery-ready Jira ticket.

## Protect credentials

1. Confirm `scripts/jira` exists and is executable.
2. Require the Git-ignored `.env.jira` configuration, but never read, print, log,
   quote, commit, or copy its contents.
3. Never pass a token on the command line or place one in a request body.
4. If authentication or authorization fails, report the HTTP status and safe error
   details. Do not expose headers or credentials and do not guess from stale context.

## Read complete ticket context

1. Fetch the requested issue:

   ```sh
   scripts/jira get TF-7
   scripts/jira comments TF-7
   ```

2. Inspect the returned parent, subtasks, issue links, status, description, and other
   fields relevant to the requested role.
3. Fetch each relevant parent, linked issue, and subtask with both `get` and
   `comments`. Do not call a hierarchy complete from the initial issue response
   alone.
4. Use JQL when a relationship is absent from the issue fields:

   ```sh
   scripts/jira search 'project = TF AND parent = TF-7 ORDER BY key'
   ```

5. Distinguish missing content from a failed request. Summarize the issue keys and
   sources actually inspected.

## Perform authorized writes

Jira writes change external shared state. Perform only the operation the user
explicitly requested or a write explicitly required by the current approved
workflow step.

1. Read the target immediately before writing.
2. Prefer a named helper command:

   ```sh
   scripts/jira comment TF-7 'Architecture review outcome...'
   scripts/jira append-description TF-7 'Additional approved text'
   ```

3. Use the generic REST escape hatch only when no named command supports the
   authorized action:

   ```sh
   scripts/jira request GET '/rest/api/3/issue/TF-7?fields=summary,status'
   scripts/jira request POST '/rest/api/3/issue/TF-7/transitions' '{"transition":{"id":"31"}}'
   ```

4. Before a generic edit, creation, link, or transition, retrieve the current issue
   and any required Jira metadata or available transitions. Preserve existing
   description content unless replacement was explicitly requested.
5. Supply valid Atlassian Document Format objects for rich-text fields when using
   generic REST requests.
6. Re-read the affected issue or comments after every write and report the verified
   result. Do not claim success from the write response alone.

Do not transition an issue or declare it complete without the evidence required by
`AGENTS.md`. Do not silently change product intent, hierarchy, acceptance criteria,
priority, assignee, or workflow state.

## Report outcomes

State:

- the issues and hierarchy inspected;
- the Jira action performed, if any;
- how the result was verified;
- any safe authentication, permission, validation, or API failure that remains.

Never include credentials or `.env.jira` contents in the report.
