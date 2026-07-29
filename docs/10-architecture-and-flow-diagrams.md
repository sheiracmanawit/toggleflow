# Architecture and Flow Diagrams

## 1. Purpose and Scope

These diagrams visualize the MVP 0.1 architecture and primary workflows defined in
the product requirements, domain architecture, and API contract. Solid components are
part of the MVP. Dashed or explicitly labeled future components show intended
extension points and are not implementation commitments for MVP 0.1.

## 2. Diagram Index

| Diagram | Question answered |
| --- | --- |
| System Context | Who uses ToggleFlow, and why? |
| Container View | Which runtime components own management and evaluation behavior? |
| Domain Model | How are users, projects, environments, flags, keys, and audit events related? |
| Primary Management Flow | How does an owner configure and release a feature? |
| Evaluation Sequence | How does a client application obtain a safe boolean value? |
| Flag State Change Sequence | How are state and audit history kept consistent? |
| API Key Lifecycle | How does an environment credential move from creation to revocation? |
| Evolution View | Where do post-MVP capabilities extend the system? |

## 3. C4 Level 1 — System Context

The system context separates human feature management from runtime evaluation. The
project owner controls release configuration; the client application consumes it.

```mermaid
flowchart LR
    owner["Project Owner<br/>Developer, QA, or Product user"]
    client["Client Application<br/>Web app, API, or backend"]
    toggleflow["ToggleFlow<br/>Self-hosted feature management platform"]

    owner -->|"Manages projects, flags, keys,<br/>and reviews audit history"| toggleflow
    client -->|"Evaluates a flag using an<br/>environment API key"| toggleflow
    toggleflow -->|"Shows release state and<br/>change history"| owner
    toggleflow -->|"Returns a boolean value<br/>and evaluation reason"| client
```

### Context boundary

- The project owner is authenticated through the management session.
- The client application is authenticated using a credential scoped to one
  environment.
- ToggleFlow does not execute the consuming application's feature. It returns the
  decision the application uses.
- Third-party identity, notifications, analytics, and billing are outside MVP 0.1.

## 4. C4 Level 2 — Container View

The MVP is one monorepo with an independently built Vue dashboard and a modular
Laravel backend connected to one authoritative database. Management and evaluation
use different entry paths even though both backend paths share domain services and
persistence. The diagram shows runtime containers, not separate repositories.

```mermaid
flowchart TB
    owner["Project Owner"]
    client["Client Application"]

    subgraph tf["ToggleFlow System"]
        ui["Vue 3 Dashboard<br/>apps/dashboard<br/>Management SPA"]

        subgraph laravel["Laravel Application<br/>apps/platform-api"]
            management["Management API<br/>Session authentication, policies,<br/>commands, and resources"]
            evaluation["Evaluation API /api/v1<br/>Environment-key authentication,<br/>rate limiting, and responses"]
            domain["Application and Domain Services<br/>Project, flag, credential,<br/>evaluation, and audit actions"]
        end

        mysql[("MySQL<br/>Authoritative configuration<br/>and audit history")]
        redis[("Redis<br/>Future evaluation cache")]
    end

    owner -->|"HTTPS"| ui
    ui -->|"JSON over HTTPS<br/>Sanctum session"| management
    client -->|"HTTPS + bearer key"| evaluation
    management --> domain
    evaluation --> domain
    domain -->|"Transactions and queries"| mysql
    evaluation -.->|"Post-MVP cached reads"| redis
    management -.->|"Post-commit invalidation"| redis

    classDef future stroke-dasharray: 6 4,color:#666;
    class redis future;
```

### Container responsibilities

| Container | Responsibility |
| --- | --- |
| Vue dashboard | Present projects, flag state, credentials, and recent changes. |
| Management API | Validate owner commands and enforce project authorization. |
| Evaluation API | Authenticate environment keys and return stable evaluation responses. |
| Domain services | Enforce invariants and coordinate state changes with audit events. |
| MySQL | Store authoritative configuration, credential hashes, and audit history. |
| Redis | Optional post-MVP acceleration; never the sole source of configuration. |

For self-hosting, a reverse proxy should normally expose the dashboard and Laravel
routes through one HTTPS origin. Local development may use separate Vite and Artisan
origins with credentialed CORS and Sanctum stateful-domain configuration.

## 5. Core Domain Model

The environment-specific state is modeled separately from the flag definition. This
allows the same flag to be disabled in Production and enabled in Development without
duplicating flag metadata.

```mermaid
erDiagram
    USER ||--o{ PROJECT : owns
    USER o|--o{ AUDIT_EVENT : performs
    PROJECT ||--|{ ENVIRONMENT : contains
    PROJECT ||--o{ FEATURE_FLAG : defines
    PROJECT ||--o{ AUDIT_EVENT : records
    ENVIRONMENT ||--o{ ENVIRONMENT_FLAG : configures
    FEATURE_FLAG ||--o{ ENVIRONMENT_FLAG : receives
    ENVIRONMENT ||--o{ API_KEY : authenticates

    USER {
        bigint id PK
        string name
        string email UK
    }

    PROJECT {
        bigint id PK
        bigint owner_id FK
        string name
        string slug
        string status
    }

    ENVIRONMENT {
        bigint id PK
        bigint project_id FK
        string name
        string key
        string color
    }

    FEATURE_FLAG {
        bigint id PK
        bigint project_id FK
        string name
        string key
        string description
        string status
    }

    ENVIRONMENT_FLAG {
        bigint id PK
        bigint environment_id FK
        bigint feature_flag_id FK
        boolean enabled
    }

    API_KEY {
        bigint id PK
        bigint environment_id FK
        string name
        string prefix UK
        string secret_hash UK
        timestamp last_used_at
        timestamp revoked_at
    }

    AUDIT_EVENT {
        bigint id PK
        bigint project_id FK
        bigint actor_id FK
        string action
        string subject_type
        bigint subject_id
        json metadata
        timestamp created_at
    }
```

### Important constraints

- A project slug is unique for its owner in MVP 0.1.
- An environment key and feature-flag key are unique within their project.
- The pair of environment and feature flag is unique in `ENVIRONMENT_FLAG`.
- An API key stores a lookup prefix and secure secret hash, never the recoverable
  secret.
- Audit metadata is allowlisted and cannot contain complete credentials.

## 6. Primary Project-owner Flow

This user flow represents the portfolio demonstration and the smallest complete
product loop.

```mermaid
flowchart TD
    start(["Start"])
    signIn["Sign in"]
    hasProject{"Project exists?"}
    createProject["Create project"]
    createEnvironments["Create Development, Staging,<br/>and Production automatically"]
    openProject["Open project"]
    createFlag["Create boolean feature flag"]
    disabled["Flag starts disabled<br/>in every environment"]
    enableNonProd["Enable in Development<br/>or Staging"]
    verify["Verify the feature<br/>in a non-production environment"]
    ready{"Ready for Production?"}
    enableProd["Enable in Production"]
    evaluate["Client evaluates the flag"]
    healthy{"Feature healthy?"}
    continueRelease["Keep feature enabled"]
    rollback["Disable in Production<br/>without redeploying"]
    audit["Review audit history"]
    finish(["Release decision complete"])

    start --> signIn --> hasProject
    hasProject -- "No" --> createProject --> createEnvironments --> openProject
    hasProject -- "Yes" --> openProject
    openProject --> createFlag --> disabled --> enableNonProd --> verify --> ready
    ready -- "No" --> enableNonProd
    ready -- "Yes" --> enableProd --> evaluate --> healthy
    healthy -- "Yes" --> continueRelease --> audit --> finish
    healthy -- "No" --> rollback --> audit --> finish
```

## 7. Runtime Evaluation Sequence

The API key determines the environment. A caller cannot request a different project
or environment in request data.

```mermaid
sequenceDiagram
    autonumber
    actor Client as Client Application
    participant API as Evaluation API
    participant Auth as API Key Authenticator
    participant Eval as Flag Evaluator
    participant DB as MySQL

    Client->>API: GET /api/v1/flags/new-checkout<br/>Bearer environment key
    API->>Auth: Authenticate bearer key
    Auth->>DB: Find prefix and compare secret hash

    alt Key is missing, invalid, or revoked
        DB-->>Auth: No active credential
        Auth-->>API: Authentication failed
        API-->>Client: 401 INVALID_API_KEY
    else Active credential
        DB-->>Auth: Environment and project identity
        Auth-->>API: Authenticated environment
        API->>Eval: evaluate(environment, flag key)
        Eval->>DB: Find active project flag and environment state

        alt Active configured flag exists
            DB-->>Eval: enabled true or false
            Eval-->>API: EvaluationResult(value, STATIC)
        else Flag missing, archived, or unconfigured
            DB-->>Eval: No eligible configuration
            Eval-->>API: EvaluationResult(false, safe reason)
        end

        API-->>Client: 200 value and reason
    end
```

### Failure behavior

- Authentication failures use `401` and do not reveal whether the requested flag
  exists.
- Missing or unavailable flags return the safe boolean `false` and a reason in MVP
  0.1.
- The consuming application must retain a local fallback for network and server
  failures.

## 8. Flag State Change and Audit Sequence

The configuration change and audit event share one database transaction. This avoids
showing a new state without its history or a history entry for a change that failed.

```mermaid
sequenceDiagram
    autonumber
    actor Owner as Project Owner
    participant UI as Vue Dashboard
    participant API as Management API
    participant Policy as Project Policy
    participant Action as SetEnvironmentFlagState
    participant Recorder as RecordAuditEvent
    participant DB as MySQL

    Owner->>UI: Enable new-checkout in Production
    UI->>API: Submit state-change command
    API->>Policy: Authorize owner and project

    alt Owner is unauthorized
        Policy-->>API: Denied
        API-->>UI: 403 Forbidden
        UI-->>Owner: Show failure; retain prior state
    else Owner is authorized
        Policy-->>API: Allowed
        API->>Action: Set enabled = true
        Action->>DB: Begin transaction
        Action->>DB: Update environment flag
        Action->>Recorder: record(flag, actor, FeatureFlagEnabled, metadata)
        Recorder->>DB: Append feature_flag.enabled event

        alt Any write fails
            Action->>DB: Roll back transaction
            Action-->>API: Change failed
            API-->>UI: Error response
            UI-->>Owner: Show failure; retain prior state
        else Both writes succeed
            Action->>DB: Commit transaction
            Action-->>API: Updated state
            API-->>UI: Success response
            UI-->>Owner: Show Production enabled
        end
    end
```

`FeatureFlag` implements `Auditable` and uses `HasAuditEvents`; the same convention
applies to every Eloquent model that can be an audit subject. `AuditEvent.action` is
cast to the shared `AuditEventAction` enum. `RecordAuditEvent` centralizes
persistence, but the application action owns the transaction so the state change and
event remain visibly atomic.

## 9. Environment API Key Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Generated: Owner requests environment key
    Generated --> ShownOnce: Secure secret generated
    ShownOnce --> Active: Owner stores secret
    Active --> Active: Successful evaluation updates last used time
    Active --> Revoked: Owner revokes key
    ShownOnce --> Revoked: Owner revokes unused key
    Revoked --> [*]

    note right of ShownOnce
        Complete secret is visible only here.
        Only prefix and hash are persisted.
    end note

    note right of Revoked
        Authentication always fails.
        Revocation is retained for history.
    end note
```

Key rotation uses two credentials temporarily: issue a replacement, deploy it to the
client application, verify successful evaluation, and then revoke the old key.

## 10. Post-MVP Evolution View

The MVP evaluator remains the entry point as capabilities grow. Future components
extend the decision process or ownership model without changing the meaning of a
project, environment, or flag key.

```mermaid
flowchart LR
    client["Client Application"]
    sdk["Future SDK<br/>Fallbacks and local cache"]
    api["Versioned Evaluation API"]
    evaluator["Evaluation Service"]
    static["MVP Static Boolean Strategy"]
    rules["Future Rule Engine<br/>Priorities and defaults"]
    segments["Future Segments<br/>Reusable audiences"]
    rollout["Future Percentage Allocation<br/>Deterministic bucketing"]
    store[("Configuration Store")]

    owner["MVP Project Owner"]
    org["Future Organization"]
    membership["Future Membership and Roles"]
    project["Project"]

    client -->|"MVP direct HTTP"| api
    client -.->|"Post-MVP"| sdk
    sdk -.-> api
    api --> evaluator
    evaluator --> static
    evaluator -.-> rules
    rules -.-> segments
    rules -.-> rollout
    static --> store
    rules -.-> store

    owner --> project
    org -.-> membership
    membership -.-> project

    classDef future stroke-dasharray: 6 4,color:#666;
    class sdk,rules,segments,rollout,org,membership future;
```

### Extension rules

- Preserve the `/api/v1` response contract or introduce a documented new version.
- Keep percentage allocation deterministic for the same flag, environment, and
  targeting key.
- Migrate existing owners into organizations before replacing ownership policies with
  membership permissions.
- Add Redis only as a cache with explicit post-commit invalidation.
- Keep evaluation telemetry separate from the append-only management audit log.

## 11. Related Documentation

- [MVP Product Requirements](06-mvp-product-requirements.md)
- [Domain and Architecture](07-domain-and-architecture.md)
- [API Contract](08-api-contract.md)
- [One-week Delivery Plan](09-delivery-plan.md)
- [Authentication and API Key Decision](11-authentication-and-api-key-decision.md)
- [Frontend Architecture and Design System](12-frontend-architecture-and-design-system.md)
- [Roadmap](roadmap.md)
