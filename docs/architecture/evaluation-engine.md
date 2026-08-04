# Evaluation Engine

## Purpose

The evaluation engine returns a safe, deterministic flag decision for a client
application while enforcing environment isolation and public API compatibility.

## Available behavior

- The client authenticates with an opaque key scoped to one environment.
- ToggleFlow resolves the project and environment from that credential.
- A boolean flag is evaluated from its environment-specific state.
- Missing, archived, or unconfigured flags return the documented safe fallback and
  reason.
- Authentication failures do not disclose whether a key prefix, project,
  environment, or flag exists.

## Planned progressive-delivery behavior

The engine will add evaluation context, Percentage Rollouts, User Targeting, ordered
Targeting Rules, Segments, explicit defaults, and explainable decision reasons.

Percentage allocation must be deterministic across instances for the same flag,
environment, rollout configuration, and stable user identity. Rule ordering, missing
attributes, privacy boundaries, and fallback behavior must be documented before the
contract is considered delivery-ready.

## Correctness rules

- Management and evaluation authentication remain separate.
- Request data never selects or overrides project or environment.
- MySQL is authoritative; a cache may improve latency but cannot be required for
  correctness.
- Public behavior remains versioned under `/api/v1` until a documented compatibility
  decision introduces another version.
- Client applications retain a local fallback for network or service failure.

See the [API Contract](api-contract.md) for the exact available wire format.
