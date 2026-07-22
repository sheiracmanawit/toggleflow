# MVP 0.1 Product Requirements

## 1. Purpose

MVP 0.1 is the first complete, demonstrable release of ToggleFlow. It must prove the
central product promise: application behavior can be changed safely without another
deployment.

This milestone is intentionally narrow enough to finish in one week, while its data
model and public boundaries are intended to support continued development.

## 2. Product Outcome

After completing the MVP, a user can:

1. Sign in to ToggleFlow.
2. Create a project.
3. Use the project's Development, Staging, and Production environments.
4. Create a boolean feature flag.
5. Enable or disable that flag independently in each environment.
6. create an environment-scoped API key.
7. Evaluate the flag from another application through the REST API.
8. Review who changed the flag and when.

## 3. Primary Demo Story

A developer deploys a new checkout implementation behind the `new-checkout` flag.
The flag is enabled in Development and Staging but remains disabled in Production.
After verification, the developer enables it in Production from ToggleFlow. If a
problem appears, the developer disables it immediately. Both changes appear in the
audit log and no application deployment is required.

## 4. Personas for the MVP

### Project Owner

The only interactive role required by the MVP. The owner manages their projects,
flags, environments, and API keys.

### SDK or Client Application

An external application that evaluates flags using an environment-scoped API key.
This actor has read-only access to evaluation data and cannot manage flags.

The broader Product Manager, QA, DevOps, and Viewer roles remain part of the product
vision. They require organizations, memberships, and permissions and are deferred to
a team-ready release.

## 5. Functional Requirements

### 5.1 Authentication

- A registered or seeded user can sign in with email and password.
- An authenticated user can sign out.
- Protected dashboard routes redirect unauthenticated users to sign in.
- Password reset and public registration may be deferred if they threaten the
  deadline; the demo must always include a documented seeded account.

### 5.2 Projects

- A user can list their projects.
- A user can create, view, rename, and archive a project.
- A project has a unique identifier, name, slug, description, and lifecycle status.
- A newly created project receives Development, Staging, and Production environments.
- A user cannot access a project they do not own.

### 5.3 Environments

- An environment belongs to exactly one project.
- Each environment has a name, key, and display color.
- Environment keys are unique within a project.
- MVP environments cannot be removed through the interface.
- Flag state is stored per environment rather than directly on the flag.

### 5.4 Feature Flags

- A user can list, search visually, create, view, edit, and archive a flag.
- A flag has a stable machine-readable key such as `new-checkout`.
- A flag key is unique within its project and cannot be changed after creation.
- A flag has a name, optional description, and lifecycle status.
- A new flag is disabled in every environment by default.
- A user can enable or disable a flag independently in each environment.
- State-changing actions require clear feedback and cannot silently fail.
- Archived flags are excluded from ordinary evaluation and return the configured
  fallback behavior.

Permanent deletion, cloning, tags, categories, and complex flag value types are not
required in MVP 0.1.

### 5.5 API Keys

- A user can create and revoke an API key for an environment.
- The full secret is displayed only once after creation.
- Only a secure hash of the secret is persisted.
- A key has a human-readable name, prefix, last-used timestamp, and revoked timestamp.
- A revoked key cannot evaluate flags.
- An API key grants read-only evaluation access to one environment.

### 5.6 Flag Evaluation

- A client can evaluate one boolean flag using an environment API key.
- The environment is derived from the API key and cannot be selected by request data.
- Evaluation returns a stable boolean result and a machine-readable reason.
- Missing or archived flags return the caller-provided default where supported, or a
  safe `false` value in the MVP endpoint.
- The API never exposes administrative models or secret material.
- API responses use a versioned `/api/v1` namespace.

### 5.7 Audit Log

- ToggleFlow records project creation and archival.
- ToggleFlow records flag creation, editing, archival, enabling, and disabling.
- ToggleFlow records API-key creation and revocation without recording secrets.
- An event contains the actor, action, subject, project, timestamp, and safe before/
  after metadata where applicable.
- Audit events are append-only through normal application behavior.

### 5.8 Dashboard

- The dashboard shows project count, active flag count, and recent changes.
- The project screen makes environment state immediately understandable.
- Empty states explain the next action.
- Loading, validation, success, and failure states are visible.
- The core workflow is usable on modern desktop and mobile-sized screens.

## 6. Explicit Non-goals

The following are not part of MVP 0.1:

- Organizations, memberships, invitations, and RBAC
- Percentage rollouts, user targeting, segments, and rules
- Scheduling and feature dependencies
- Experiments, analytics, and usage ingestion
- Notifications and third-party integrations
- Published PHP, JavaScript, Vue, React, or mobile SDKs
- Real-time streaming updates
- Billing, SSO, Terraform, Kubernetes, and multi-region operation

These omissions limit delivery scope; they do not remove the capabilities from the
long-term roadmap.

## 7. Acceptance Criteria

MVP 0.1 is complete when all of the following are true:

- A fresh installation can be started by following the README.
- Demo data can be produced with one documented command.
- The complete primary demo story works without manual database changes.
- Project ownership is enforced on every administrative endpoint.
- Evaluation keys are scoped to one environment and stored securely.
- Toggling Production changes the next evaluation response.
- Relevant changes appear in an append-only audit history.
- Automated tests cover the primary workflow, authorization, invalid keys, missing
  flags, and environment isolation.
- The interface has no broken navigation or unfinished placeholder screens in the
  demonstrated workflow.
- The repository explains the architecture, API, tradeoffs, and future roadmap.

## 8. Product Metrics

The portfolio release should be judged using demonstrable measures:

- A flag can be created in under 30 seconds.
- A sample client can evaluate its first flag in under 5 minutes using the API guide.
- A production flag can be disabled in one dashboard action.
- An evaluated flag reflects a completed state change on the next uncached request.
- All management changes relevant to release safety are traceable.

Percentage-based gradual rollout is intentionally not an MVP success criterion. It
becomes one when the rule engine is delivered.

## 9. Definition of Done for Each Feature

A feature is done only when:

- Backend validation and authorization are implemented.
- The user interface includes loading, empty, error, and success behavior.
- Relevant audit events are emitted.
- Happy-path and important failure-path tests pass.
- User-facing documentation is updated.
- No secret or sensitive value is logged or exposed after its intended display.
