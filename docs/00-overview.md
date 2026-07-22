# ToggleFlow Overview

## What is ToggleFlow?

ToggleFlow is an open-source feature management platform that enables software teams to safely release software by separating deployment from feature release.

Instead of using environment variables or maintaining long-lived feature branches, teams can control feature availability through a centralized dashboard and REST API.

## Product Goals

- Release software safely
- Reduce deployment risk
- Support gradual rollouts
- Instantly disable problematic features
- Improve collaboration between Engineering, QA, and Product

## Core Capabilities

- Projects
- Environments
- Feature Flags
- Percentage Rollouts
- User Targeting
- Audit Logs
- REST API

## MVP 0.1 Scope

Included:
- Authentication
- Projects
- Environments
- Boolean Feature Flags
- Audit Logs
- API Keys
- Dashboard
- Evaluation API

Excluded:
- Organizations
- Percentage Rollouts
- User Targeting
- A/B Testing
- Analytics
- Enterprise SSO
- Notifications

Excluded items are deferred, not abandoned. The MVP establishes the core domain and
evaluation boundary that later releases will extend.

## Success Criteria

A user should be able to create a project, configure a boolean flag independently in
each environment, evaluate it through an authenticated API, and disable it instantly
without redeploying. Gradual release becomes a success criterion when rollout rules
ship after the MVP.
