# Domain Model

## Purpose

This document defines the product concepts and relationships that must remain stable
as ToggleFlow grows.

## Core concepts

- A **User** authenticates to the management dashboard.
- An **Organization** is the planned team ownership boundary.
- A **Project** represents an application or service whose releases are managed.
- An **Environment** belongs to one project and isolates release state and evaluation
  credentials.
- A **Feature Flag** belongs to one project and defines stable flag identity and
  lifecycle metadata.
- **Environment State** stores the value or rollout configuration for one flag in one
  environment.
- An **Environment API Key** grants evaluation access to exactly one environment.
- An **Audit Event** records a meaningful management action.
- **Evaluation Context** is planned request data containing a stable subject identity
  and attributes used by targeting.
- A **Targeting Rule** is a planned ordered condition that can select a variation.
- A **Segment** is a planned reusable cohort referenced by targeting rules.

## Ownership model

The available product uses direct user ownership of projects. Organization membership
and permissions are planned. Until that migration is delivered, documentation and
code must not imply that team access exists.

## Evaluation boundary

The evaluation credential resolves project and environment. Request context cannot
override either boundary. Flag metadata remains separate from environment-specific
state so rollout behavior can evolve without duplicating flag identity.

## Lifecycle

Projects, flags, and credentials use explicit lifecycle behavior. Ordinary product
workflows archive or revoke records rather than erasing operational history.

Detailed implementation boundaries are maintained in the
[Architecture Overview](overview.md).
