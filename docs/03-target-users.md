# Target Users

## Design center

ToggleFlow is designed first for small engineering teams—roughly two to twenty
people—running several applications or services. They have outgrown environment
variables or custom toggle tables but do not need the cost and complexity of a broad
enterprise platform.

## Available actor

### Release owner

A developer or technical owner who directly owns projects and needs to:

- understand Development, Staging, and Production release state;
- create and manage boolean flags;
- issue environment-scoped evaluation credentials;
- enable, disable, and reverse a release deliberately; and
- investigate management history.

The current product has one interactive ownership model. Product Manager, QA, and
DevOps labels do not imply distinct permissions until team governance is delivered.

### Client application

A backend, API, worker, or other trusted application that evaluates flags for one
environment. It needs a stable response, non-enumerating authentication failure, and
safe local fallback when ToggleFlow cannot be reached.

## Near-term users

### Integrating developer

Needs OpenAPI, OpenFeature compatibility, supported language integrations, examples,
timeouts, caching guidance, and predictable upgrade behavior.

### QA engineer

Needs understandable environment state and controlled access to verify unreleased
behavior without changing Production.

### Product or release collaborator

Needs clear release state, attributable changes, and later an approval path without
broad administrative access.

### Self-hosting platform engineer

Needs documented installation, configuration, security, observability, backup,
restore, upgrade, recovery, and capacity guidance.

## Organizations most likely to benefit

- Startups and small SaaS teams
- Agencies operating several client applications
- Internal platform teams seeking a focused self-hosted control plane
- Open-source projects that value transparent operation and portable data

## Users outside the initial design center

Large enterprises requiring formal compliance certification, complex custom roles,
SSO/SCIM, procurement controls, global multi-region guarantees, or native
experimentation are later markets. Their needs should not distort the earlier product
before the core platform is proven.
