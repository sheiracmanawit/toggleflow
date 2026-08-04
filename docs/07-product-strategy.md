# ToggleFlow Product Strategy

## 1. Strategy at a glance

ToggleFlow is the transparent, self-hosted feature release platform that small
engineering teams can understand, operate, and trust.

For teams that have outgrown environment variables or custom toggle tables but do not
need an opaque enterprise control plane, ToggleFlow combines predictable evaluation,
secure environment isolation, reversible release controls, auditability, and
straightforward ownership in one focused open-source product.

The strategy is sequential:

1. Make the existing product easy to extend and confident to use.
2. Make releases gradual and explainable.
3. Make application integration resilient and standardized.
4. Make multi-person release control accountable.
5. Make self-hosting dependable enough for a supported 1.0.
6. Add release intelligence only after exposure and operational data are trustworthy.

## 2. Competitive position

ToggleFlow competes with LaunchDarkly, ConfigCat, and Flagsmith through clarity,
ownership, and self-hosting—not immediate feature-count parity.

| Product | Useful benchmark | ToggleFlow response |
| --- | --- | --- |
| LaunchDarkly | Deep progressive delivery, integrations, governance, and guarded releases | Learn from mature release workflows without importing enterprise breadth before the core is ready. |
| ConfigCat | Approachable controls and broad SDK coverage | Keep configuration understandable and make integration semantics consistent. |
| Flagsmith | Open-source and self-hosted feature management | Treat installation, upgrades, recovery, and operations as product capabilities rather than deployment footnotes. |

Competitor behavior and packaging change. Re-check primary product documentation
during roadmap reviews rather than maintaining a permanent feature-parity matrix.

## 3. Design center

### Primary: small engineering team

A two-to-twenty-person team running several services needs safer releases, fast
rollback, and a shared record of changes without the cost or operational opacity of a
broad enterprise suite.

### Secondary: self-hosting platform engineer

A platform-minded developer needs a service that is easy to deploy, secure, monitor,
back up, upgrade, recover, and integrate with delivery tooling.

### Later: product and release collaborator

A product, QA, or release stakeholder needs understandable state, controlled access,
approvals, and evidence without broad infrastructure privileges.

Enterprise procurement, formal compliance certification, and large-company identity
provisioning are not the initial design center.

## 4. Product pillars

### Understandable release control

**Outcome:** a release owner can understand current state, change it deliberately,
recover from failure, and trace what happened.

The immediate investment is the bounded backend, feature-oriented dashboard, Nuxt UI
foundation, centralized ToggleFlow theme, and workflow-based dashboard redesign.

### Safe progressive delivery

**Outcome:** a team can release to a stable subset of subjects, target intended
cohorts, understand the winning decision, and roll back immediately.

The first capabilities are evaluation context, Percentage Rollouts, User Targeting,
ordered Targeting Rules, reusable Segments, explainable reasons, and
scalable flag discovery.

### Excellent developer experience

**Outcome:** a developer can integrate ToggleFlow quickly while retaining safe local
behavior when the control plane is unavailable.

The first capabilities are OpenAPI, OpenFeature compatibility, PHP/Laravel and
JavaScript/TypeScript integrations, examples, timeouts, local caching guidance,
fallback behavior, versioning, and webhooks.

### Accountable team control

**Outcome:** the right people can view, propose, approve, and make changes at the right
scope.

The first capabilities are organizations, invitations, small predefined roles,
environment-aware permissions, Production change reasons, and approvals.

### Dependable self-hosting

**Outcome:** an operator can install, observe, upgrade, restore, and troubleshoot
ToggleFlow from public documentation.

The first capabilities are supported deployment, configuration reference, health and
readiness, metrics, backup and restore, upgrade notes, recovery exercises, and
capacity guidance.

### Release intelligence

**Outcome:** a team can connect rollout state with external service or product
signals and automate bounded safeguards.

Scheduling and external integrations precede native experimentation. Automatic
decisions require reliable exposure data, metric definitions, statistical review,
guardrails, and explicit human override behavior.

## 5. Immediate product program

### Engineering foundation

- TF-22 establishes bounded Laravel modules.
- TF-23 provides secure automated pull-request review.
- TF-24 establishes complete feature-oriented Vue ownership.
- TF-26 establishes Nuxt UI, semantic tokens, an indigo-and-zinc ToggleFlow theme,
  accessible primitives, and contributor conventions.

### Dashboard experience

TF-27 makes the current release-management product clear and confident across:

- navigation and project context;
- portfolio release state;
- project creation and workspace management;
- flag discovery and environment controls;
- API-key issuance and revocation;
- audit-history comprehension;
- authentication entry; and
- responsive and accessible behavior.

This program precedes new progressive-delivery UI implementation. It does not add
targeting, approvals, analytics, or production-operation claims.

## 6. Roadmap hypotheses

The roadmap contains hypotheses to validate, not commitments to copy a competitor.

- Small teams value deterministic rollout and explainable decisions before advanced
  experimentation.
- OpenFeature and a small number of excellent integrations create more adoption value
  than a shallow SDK for every language.
- Self-hosting is differentiating only when upgrades and recovery are trustworthy.
- Simple predefined permissions should precede custom role builders.
- External observability integration should precede a proprietary analytics
  warehouse.

Each release should gather evidence through installation exercises, workflow tests,
integration feedback, defect patterns, and external adopter conversations.

## 7. Success measures

| Objective | Measure | Direction |
| --- | --- | --- |
| Fast adoption | Time from clean checkout to first successful evaluation | Reduce through documentation and supported integration paths. |
| Clear control | Completion and error-recovery success for core dashboard workflows | Improve during TF-27 and maintain thereafter. |
| Safe release | Deterministic evaluation, isolation, rollback, and audit contract tests | Pass for every release. |
| Resilient integration | Supported clients demonstrate documented fallback behavior | Required for every published integration. |
| Operable self-hosting | Clean install, upgrade, backup, restore, and recovery exercises | Pass before production-ready claims. |
| Sustainable delivery | Critical security and compatibility defects | No known unresolved critical issue at release. |
| Useful product | External adopters completing the core release flow | Establish a baseline and review each minor release. |

Raw flag count, evaluation volume without context, and repository stars are not
primary evidence of product value.

## 8. Decision gates

Before starting a capability, answer:

1. Which target user and release problem does it serve?
2. What is the smallest independently useful outcome?
3. What public contract, stored data, security boundary, or upgrade path changes?
4. How does an operator or user recover when it fails?
5. What evidence determines whether to continue, revise, or stop?

Additional gates:

- Do not publish an SDK without shared evaluation semantics and compatibility tests.
- Do not add Redis without measured need; MySQL remains authoritative.
- Do not add organization tenancy without migration and isolation tests.
- Do not claim high availability without a tested topology and recovery procedure.
- Do not build native experimentation before trustworthy exposure data and
  statistical methodology exist.

## 9. Documentation ownership

- `00-overview.md` explains the documentation model and sources of truth.
- `01-product-vision.md` owns stable vision, positioning, and principles.
- `04-product-capabilities.md` owns the capability catalog and status.
- `05-product-requirements.md` owns durable outcomes, behavior, and boundaries.
- `08-roadmap.md` owns sequencing, target windows, release outcomes, and gates.
- `architecture/` owns system boundaries and target technical direction.
- `engineering/` owns contributor, delivery, testing, and CI guidance.
- `decisions/` records accepted durable architecture decisions.
- This document owns competitive position, pillars, hypotheses, measures, and
  decision gates.

Jira owns delivery-ready detail. Material strategy or architecture decisions must
also be recorded in the repository so the product does not depend on private backlog
context.

## 10. Review cadence

- Review product priorities and evidence at the end of every minor release.
- Review architecture, security, data, dependency, and operational impact before an
  epic becomes development-ready.
- Update affected documentation in the same change as product behavior.
- Re-check competitor references and roadmap assumptions at least once per minor
  release.
