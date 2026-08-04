# Product Quality Requirements

## Performance
- Establish evaluation latency baselines in a documented reference deployment before
  setting support objectives.
- Do not claim cached or high-availability performance without measured evidence.
- Keep MySQL authoritative; a cache must never be required for correctness.

## Security
- Use Sanctum cookie authentication for the first-party dashboard.
- Use opaque, hashed, environment-scoped credentials for evaluation.
- Enforce current project ownership and future team permissions on the server.
- Never persist or log plaintext credentials, passwords, bearer headers, or
  unfiltered sensitive request data.
- Make authentication errors non-enumerating.

## Reliability
- Client applications retain local fallback behavior when ToggleFlow is unavailable.
- State changes and required audit records commit or roll back together.
- Failed management mutations preserve the last confirmed server state.
- Backup, restore, upgrade, and recovery become verified release gates before a
  production-ready support claim.

## Maintainability
- Follow the checked-in engineering, architecture, and repository standards.
- Preserve bounded ownership and public contracts as capabilities grow.
- Require formatting, static analysis, type checking, tests, and builds appropriate
  to every change.
- Update affected product, API, architecture, operations, and upgrade documentation
  with behavior changes.

## Usability
- Complete critical workflows on supported desktop, tablet, and mobile layouts.
- Make all core actions keyboard operable with visible focus and correct focus
  management.
- Never communicate state or environment through color alone.
- Provide explicit loading, empty, validation, success, and relevant failure states.
- Use supported product data and avoid decorative or invented analytics.

## Compatibility
- Preserve fields and meanings within a published API version.
- Document migrations, deprecations, and recovery for incompatible changes.
- Verify supported SDKs and providers against shared contract tests.

## Operability
- Provide public installation and configuration instructions.
- Add deployment, health, backup, restore, upgrade, and troubleshooting guarantees
  only as their roadmap release delivers and verifies them.
