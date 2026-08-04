# ADR 001: Laravel Modular Monolith

**Status:** Accepted

ToggleFlow uses one Laravel modular monolith in `apps/platform-api`. Bounded modules
provide ownership and dependency direction without introducing distributed runtime
failure modes. Service extraction requires measured scaling, security, or independent
release need and a new architecture decision.
