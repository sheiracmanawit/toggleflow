# ToggleFlow Documentation Overview

ToggleFlow is an open-source, self-hosted feature release platform. It separates
deployment from release through understandable controls, predictable evaluation,
secure environment isolation, and an auditable history of management changes.

## Documentation model

The documentation describes the complete product vision without presenting every
capability as already delivered. Each capability uses one status:

- **Available:** implemented and usable now.
- **Committed:** approved direction with accepted or prepared delivery work.
- **Planned:** accepted product scope that requires delivery-ready stories and
  technical review.
- **Exploring:** a hypothesis requiring validation before it becomes product scope.

Capability status is not implementation authorization. Only an approved,
delivery-ready Jira ticket authorizes a product change.

## Product documentation

- [Product Vision](01-product-vision.md)
- [Problem](02-problem.md)
- [Target Users](03-target-users.md)
- [Product Capabilities](04-product-capabilities.md)
- [Product Requirements](05-product-requirements.md)
- [Quality Requirements](06-quality-requirements.md)
- [Product Strategy](07-product-strategy.md)
- [Product Roadmap](08-roadmap.md)

Percentage Rollouts, User Targeting, Targeting Rules, Segments, SDKs, team
governance, production operations, and release intelligence are visible product
capabilities with honest delivery status—not a separate future product.

## Architecture documentation

- [Architecture Overview](architecture/overview.md)
- [Domain Model](architecture/domain-model.md)
- [Authentication and API Keys](architecture/authentication-and-api-keys.md)
- [Evaluation Engine](architecture/evaluation-engine.md)
- [API Contract](architecture/api-contract.md)
- [Frontend Architecture](architecture/frontend-architecture.md)
- [System Diagrams](architecture/system-diagrams.md)

Architecture documents distinguish target design from available implementation and
identify the ticket responsible for an incomplete migration.

## Engineering documentation

- [Coding Standards](engineering/coding-standards.md)
- [Repository Structure](engineering/repository-structure.md)
- [Delivery Workflow](engineering/delivery-workflow.md)
- [Testing Strategy](engineering/testing-strategy.md)
- [Git and CI](engineering/git-and-ci.md)

## Architecture decisions

Accepted, durable decisions live in [decisions](decisions/). ADRs explain why a
direction was chosen; architecture documents explain how the system is intended to
work.

## Source-of-truth rule

Product documents own intent and capability status. Architecture documents own
technical boundaries. Jira owns delivery-ready scope and acceptance criteria. When
these sources conflict, route product intent to the Product Owner and technical
direction to the System Architect rather than choosing silently.
