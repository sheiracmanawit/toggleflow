# ToggleFlow Product Capabilities

This catalog describes the product ToggleFlow is building. Capabilities remain part
of the product vision even when they have not been delivered yet.

## Capability status

- **Available:** implemented and usable in the product now.
- **Committed:** approved direction with delivery work accepted or being prepared.
- **Planned:** accepted product scope that still needs delivery-ready stories and
  technical review.
- **Exploring:** a hypothesis that requires validation before it becomes product
  scope.

Status communicates maturity; it does not authorize implementation. Only an approved,
delivery-ready Jira ticket authorizes product changes.

## Release control

### Feature Flags

**Status:** Available

Create, edit, archive, enable, and disable boolean feature flags independently in
Development, Staging, and Production.

### Percentage Rollouts

**Status:** Planned

**Product area:** Progressive Delivery

Release owners can expose a flag to a deterministic percentage of eligible users.
The same user receives a stable result unless rollout configuration or evaluation
identity changes.

### User Targeting

**Status:** Planned

**Product area:** Progressive Delivery

Release owners can include or exclude specific users through evaluation context
without embedding user lists in application code.

### Targeting Rules

**Status:** Planned

**Product area:** Progressive Delivery

Ordered rules evaluate user and application attributes predictably, with explicit
fallback behavior and an explainable winning decision.

### Segments

**Status:** Planned

**Product area:** Progressive Delivery

Reusable segments define cohorts once and apply them consistently across flags.

### Scheduling and Guarded Rollouts

**Status:** Exploring

**Product area:** Release Intelligence

Time-based changes and signal-driven safeguards will be evaluated after progressive
delivery and trustworthy operational integrations exist.

## Projects, environments, and discovery

### Projects and Environments

**Status:** Available

Projects group flags by application. Environment-specific state and credentials keep
Development, Staging, and Production isolated.

### Flag Search and Tags

**Status:** Planned

Release owners can find and classify flags as portfolios grow.

### Organizations and Membership

**Status:** Planned

Teams can share ownership through organizations, invitations, and explicit
membership without weakening project isolation.

### Roles, Approvals, and Change Reasons

**Status:** Planned

Project and environment permissions, Production approvals, and recorded reasons make
multi-person release control accountable.

## Evaluation and developer integration

### Evaluation API

**Status:** Available

Applications evaluate boolean flags through a versioned REST endpoint using opaque,
hashed credentials scoped to exactly one environment.

### Evaluation Context and Decision Reasons

**Status:** Planned

Applications provide the stable identity and attributes required by targeting and
receive safe, explainable evaluation outcomes.

### OpenAPI and OpenFeature

**Status:** Planned

A published OpenAPI contract and OpenFeature-compatible provider standardize
integration without making clients depend on dashboard internals.

### Supported SDKs

**Status:** Planned

PHP/Laravel and JavaScript/TypeScript integrations provide compatibility-tested
evaluation, timeouts, local fallback, caching guidance, and examples. Additional SDKs
are evaluated after these integrations are stable.

### Webhooks

**Status:** Planned

Outbound events connect release changes to delivery and operational tooling.

## Management experience

### Dashboard Workflows

**Status:** Available

Authenticated release owners manage projects, flags, environment state, credentials,
and audit history through the Vue dashboard.

### Product Foundation and UX Redesign

**Status:** Committed

TF-22, TF-23, TF-24, TF-26, and TF-27 establish bounded backend modules, dependable
automated review, feature-oriented frontend ownership, Nuxt UI with the ToggleFlow
theme, and clear accessible workflows across desktop and mobile.

### Audit History

**Status:** Available

Management changes record actor, subject, timestamp, action, and safe metadata without
exposing credentials.

## Self-hosting and operations

### Supported Deployment and Recovery

**Status:** Planned

Container deployment, configuration guidance, backup, restore, upgrade, and recovery
procedures make self-hosting an explicit product capability.

### Health, Readiness, and Metrics

**Status:** Planned

Operators can observe service health and capacity without relying on unsafe or
invented dashboard analytics.

### High Availability and Evaluation Caching

**Status:** Planned

Measured caching and tested deployment guidance improve scale while MySQL remains
authoritative for correctness.

### Release Analytics and Experimentation

**Status:** Exploring

Analytics integrations precede native experimentation. ToggleFlow will not claim
statistically trustworthy experiments until exposure data, metric definitions, and
methodology are proven.

Release order and gates are maintained in the [Product Roadmap](08-roadmap.md).
