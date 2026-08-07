# ToggleFlow Product Requirements

## 1. Purpose

This document defines maintained requirements across the complete ToggleFlow product
vision while distinguishing delivered behavior from future scope.

ToggleFlow is an open-source, self-hosted feature release platform for small
engineering teams. It separates deployment from release through understandable,
reversible controls, predictable evaluation, secure environment isolation, and an
auditable record of management changes.

## 2. Requirement status

Every requirement belongs to one of four states:

- **Available:** implemented product behavior that must remain compatible.
- **Committed:** approved direction with accepted or prepared delivery work.
- **Planned:** accepted product scope that requires delivery-ready stories and
  technical review.
- **Exploring:** a hypothesis requiring validation before it becomes product scope.

Roadmap placement does not make a capability ready for development. Jira owns
delivery detail; this document owns product intent and durable boundaries.

## 3. Product outcomes

ToggleFlow enables a release owner to:

1. Separate deployment from feature availability.
2. Understand the release state of each project and environment.
3. Change feature availability without redeploying an application.
4. Reverse a release decision quickly when a problem appears.
5. Integrate applications through a stable, environment-scoped evaluation contract.
6. Trace meaningful management changes without exposing credentials or sensitive
   request data.
7. Self-host and evolve the platform without depending on a proprietary control
   plane.

The product grows from boolean release control into progressive delivery, developer
integration, team governance, and dependable production operation in that order.

## 4. Target actors

### Release owner — Available

The authenticated person who owns projects and manages flags, environments,
credentials, and audit history. The current ownership model is intentionally direct.

### Client application — Available

A web application, API, worker, or backend service that evaluates flags using an
opaque credential scoped to exactly one environment. It has no management access.

### Engineering team — Planned

Developers, QA engineers, product collaborators, and platform engineers who share
projects through explicit membership and least-privilege permissions. Team behavior
does not exist until the organization and authorization model is delivered.

### Self-hosting operator — Planned

The person responsible for installation, configuration, upgrades, recovery,
observability, and capacity of a production ToggleFlow deployment.

## 5. Available product requirements

### 5.1 Authentication and access

- A configured user can sign in and sign out through the first-party dashboard.
- Protected dashboard routes require a valid Sanctum cookie-based session.
- Every management resource is scoped to a project owned by the authenticated user.
- Client-side guards improve navigation but never replace server authorization.
- Authentication failures do not disclose internal identities or secret material.

### 5.2 Projects and environments

- A release owner can list, create, view, rename, and archive projects.
- A project has stable identity, mutable descriptive metadata, and an active or
  archived lifecycle.
- A new project receives Development, Staging, and Production environments.
- Environment keys are unique within a project.
- Ordinary product behavior archives lifecycle resources rather than permanently
  deleting them.
- One owner cannot access another owner's project or related resources.

### 5.3 Boolean feature flags

- A release owner can list, create, view, edit, and archive boolean flags.
- A flag has a stable machine-readable key unique within its project.
- Descriptive flag metadata is separate from environment-specific release state.
- A new flag starts Disabled in every environment.
- A release owner can enable or disable a flag independently in each environment.
- Production-environment changes require deliberate confirmation.
- Failed mutations preserve the last server-confirmed state and provide useful
  feedback.
- Archived flags are excluded from ordinary evaluation.

### 5.4 Environment API keys

- A release owner can issue and revoke multiple keys for an environment.
- The complete secret is displayed once and is never recoverable afterward.
- Only a lookup prefix and one-way secret hash are persisted.
- A key grants read-only evaluation access to exactly one environment.
- Revocation takes effect on the next authentication attempt.
- Key creation and revocation are audited without recording the secret.

### 5.5 Evaluation API

- Public evaluation endpoints remain versioned below `/api/v1`.
- The environment and project are resolved exclusively from the evaluation key.
- Request data cannot override credential scope.
- The current endpoint returns a boolean value and a stable machine-readable reason.
- Missing, archived, or unconfigured flags return the documented safe behavior.
- Authentication errors do not reveal whether a key prefix, project, environment, or
  flag exists.
- Client applications retain a local fallback for network, timeout, and server
  failures.

### 5.6 Audit history

- ToggleFlow records meaningful project, flag, release-state, and credential changes.
- An event identifies its actor when available, action, subject, project, timestamp,
  and allowlisted metadata.
- Audit events are append-only through ordinary application behavior.
- A state change and its required audit event commit or roll back together.
- Passwords, bearer headers, complete API keys, hashes, and unfiltered request data
  never enter audit metadata.

### 5.7 Dashboard experience

- The dashboard communicates project and release state using supported data.
- Project context remains clear across project-scoped workflows.
- Loading, empty, validation, success, and relevant failure states are explicit.
- State is never communicated through color alone.
- Critical workflows are keyboard operable and usable on modern desktop and
  mobile-sized screens.
- Disabled is a normal state; danger styling is reserved for failures and destructive
  actions.

## 6. Committed product requirements

The immediate approved work strengthens the product before new release behavior is
added:

- **TF-22:** move the current Laravel backend into bounded modules without changing
  public behavior.
- **TF-23:** provide secure automated pull-request review without replacing CI or
  independent human judgment.
- **TF-24:** fully migrate the dashboard to a feature-oriented Vue architecture.
- **TF-26:** establish Nuxt UI and centralized ToggleFlow light and dark themes with
  a browser-local Light, Dark, or System preference in the existing Vue/Vite
  application. Both presentations use one teal/mint primary brand family; violet is
  Production-only and emerald remains success/Enabled.
- **TF-27:** redesign the dashboard workflows so release state and management actions
  are clear, consistent, accessible, and responsive.

TF-24 precedes TF-26, and TF-26 precedes implementation of TF-27. These are foundation
and experience requirements, not authorization for rollout rules, organizations,
analytics, or other planned capabilities.

## 7. Planned product requirements

### 7.1 Progressive delivery

- Accept privacy-conscious evaluation context with a stable targeting key.
- Support **Percentage Rollouts** that allocate identified users deterministically
  and remain stable across application instances.
- Support **User Targeting** for explicitly included or excluded users.
- Evaluate ordered rules with explicit fallbacks and explainable reasons.
- Reuse named segments across targeting rules.
- Preserve immediate rollback and stable results across application instances.
- Add search and classification that help owners manage a growing flag portfolio.

### 7.2 Developer experience

- Publish an OpenAPI contract and compatibility policy.
- Provide an OpenFeature-compatible integration.
- Publish supported PHP/Laravel and JavaScript/TypeScript integrations.
- Standardize timeouts, local caching, fallback behavior, and upgrade guidance.
- Deliver webhooks only with safe retry, signing, and observability behavior.

### 7.3 Team governance

- Introduce organizations, membership, invitations, and migration of existing owners.
- Enforce project- and environment-aware permissions on the server.
- Add attributable Production change reasons and approval workflows.
- Preserve cross-organization isolation and least privilege.

### 7.4 Production operations

- Provide a supported container deployment and configuration reference.
- Expose health, readiness, and measured operational signals.
- Document and test backup, restore, upgrade, rollback, and recovery procedures.
- Introduce caching or topology changes only from measured need while keeping the
  authoritative configuration model explicit.

### 7.5 Release intelligence

- Add scheduling, guarded releases, and external analytics or observability
  integrations only after the core platform is operationally trustworthy.
- Do not ship native experimentation until exposure data, metric definitions,
  statistical methodology, guardrails, and human override behavior are reliable.

These capabilities remain **Exploring** until validation promotes them into Planned
scope.

## 8. Durable product boundaries

- ToggleFlow is not pursuing immediate feature parity with a commercial enterprise
  suite.
- A hosted multi-tenant ToggleFlow cloud service and billing system are not planned
  through 1.0.
- Microservice extraction requires measured scaling, availability, security, or
  release-cadence evidence.
- MySQL remains authoritative unless an approved architecture decision changes data
  ownership.
- Redis or another cache cannot be required for evaluation correctness.
- Public API changes follow versioning and documented migration rules.
- New team roles cannot be simulated through client-only visibility rules.
- Analytics, experiments, AI-driven decisions, SSO, and SCIM do not precede their
  documented foundations.

## 9. Product measures

- A new operator can reach a successful evaluation from a clean checkout using only
  public documentation.
- A release owner can identify current environment state and reverse a boolean release
  without redeployment.
- Evaluation and management isolation tests pass for every release.
- Every supported SDK or provider demonstrates safe fallback during service failure.
- Installation, upgrade, backup, and restore exercises pass before a production-ready
  support claim.
- No known critical security, isolation, secret-handling, or compatibility defect is
  accepted into a release.

Targets evolve in the roadmap as evidence becomes available. Vanity measures such as
raw flag count or repository stars do not replace workflow success and reliability.

## 10. Definition of done

A product change is done only when:

- Its Jira story or task has approved scope and acceptance criteria.
- Required Product Owner and System Architect decisions are resolved.
- Authorization, isolation, validation, failure, and secret-handling behavior is
  implemented where relevant.
- Important happy and negative paths are tested.
- Required CI passes for the reviewed commit.
- Responsive and accessibility behavior is verified for affected workflows.
- Public contracts, architecture, operations, and user documentation remain accurate.
- The change does not silently introduce a later roadmap capability.
