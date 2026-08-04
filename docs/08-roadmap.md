# ToggleFlow Product Roadmap

## 1. Purpose

This roadmap describes ToggleFlow's maintained product direction. It is a planning
instrument, not a promise of exact dates. Release gates and user evidence take
precedence over calendar targets.

ToggleFlow will compete by being the most understandable self-hosted feature release
platform for small engineering teams: secure defaults, predictable evaluation,
straightforward operations, and no enterprise machinery before it is needed.

The detailed strategy, prioritization method, competitive context, success measures,
and release gates are in [Product Strategy](07-product-strategy.md).

## 2. Delivery assumptions

- Planning baseline: 3 August 2026.
- Capacity: one primary developer, with work delivered as small vertical slices.
- Cadence: a releasable increment every two to four weeks and a minor product release
  approximately every eight to twelve weeks.
- Dates below are target windows. A release moves when its exit criteria are met.
- Security, evaluation compatibility, clean installation, and rollback safety cannot
  be traded for schedule.

## 3. Release timeline

| Target window | Release | Product outcome | Principal scope | Exit gate |
| --- | --- | --- | --- | --- |
| August–September 2026 | **Foundation and experience** | Contributors can extend ToggleFlow safely and release owners can use its available workflows confidently. | TF-22 bounded backend modules; TF-23 secure automated review; TF-24 feature-oriented Vue architecture; TF-26 Nuxt UI and ToggleFlow theme; TF-27 dashboard UX redesign; documentation and release discipline. | No public behavior regression; architecture migrations are complete; the available release loop is accessible and responsive; required checks pass; documentation is current. |
| October–November 2026 | **0.2 — Progressive delivery** | A team can expose a feature gradually and consistently instead of switching an entire environment at once. | Evaluation Context; Percentage Rollouts; User Targeting; ordered Targeting Rules; reusable Segments; flag search and tags. | The same user receives a stable result across instances; rule order, privacy, missing context, and fallbacks are documented and tested; rollback remains immediate. |
| December 2026–January 2027 | **0.3 — Developer experience** | Developers can integrate and operate ToggleFlow without hand-written HTTP plumbing. | OpenAPI contract; OpenFeature-compatible provider; first server-side SDK (PHP/Laravel); JavaScript/TypeScript SDK; local caching and resilient fallback guidance; webhooks. | Published packages have compatibility tests and examples; API changes follow versioning policy; an application remains safe during ToggleFlow unavailability. |
| February–April 2027 | **0.4 — Team collaboration** | Multiple people can manage releases with least privilege and accountability. | Organizations; membership and invitations; project/environment roles; Production approvals and change reasons; migration of existing owners. | Existing installations upgrade without losing ownership; permissions are server-enforced and isolation tests pass; critical Production changes are attributable. |
| May–July 2027 | **0.5 — Production operations** | A self-hosting team can run, observe, back up, and upgrade ToggleFlow confidently. | Supported container deployment; health/readiness endpoints; metrics; backup/restore and upgrade runbooks; evaluation caching where measured; high-availability guidance; outbound integrations. | A documented production-like deployment survives restart and upgrade; availability and latency objectives are measured; MySQL remains authoritative. |
| August–October 2027 | **1.0 — Team-ready self-hosted release platform** | A team can adopt ToggleFlow for real production release control with a stable support and compatibility contract. | Stabilization of earlier releases; lifecycle/technical-debt views; import/export; security review; long-term support and deprecation policy. | Release checklist, threat review, upgrade test, recovery exercise, accessibility pass, and public compatibility commitments are complete. |
| After 1.0 | **1.x — Release intelligence** | Teams can automate safer release decisions after the core platform has trustworthy exposure and operational data. | Scheduling; guarded rollouts; analytics integrations; experiments; SSO/SCIM; additional SDKs and infrastructure integrations. | Each capability is validated independently; experimentation is not shipped until exposure data and statistical design are trustworthy. |

## 4. Priority order

When capacity forces a choice, use this order:

1. Evaluation correctness, security, isolation, and compatibility.
2. The Committed foundation and UX program: maintainable architecture, dependable
   review, a coherent design system, and clear available workflows.
3. Progressive delivery: stable rollouts, targeting, rules, and immediate rollback.
4. Integration quality: OpenAPI, OpenFeature, SDKs, fallback behavior, and webhooks.
5. Collaboration: organizations, permissions, approvals, and auditability.
6. Production operations: deployment, observability, recovery, and measured caching.
7. Release intelligence: scheduling, automated safeguards, and experimentation.

## 5. Explicit non-goals through 1.0

- Feature parity with any single commercial competitor.
- A hosted multi-tenant ToggleFlow cloud service or billing system.
- Microservice extraction without measured scaling, security, or release-cadence need.
- A custom analytics warehouse or general-purpose observability platform.
- Mobile, edge, and every-language SDK coverage before the first SDKs are stable.
- Enterprise SSO, SCIM, or advanced compliance claims before the team-ready core is
  proven.
- AI-driven release decisions before deterministic rules, reliable telemetry, and
  human-governed safeguards exist.

## 6. Roadmap governance

Review this roadmap at the end of every minor release. Record material direction
changes in the product strategy and affected architecture documents before changing
Jira delivery scope. Product stories must still pass Product Owner and System
Architect review; roadmap placement alone does not make a story ready for development.
