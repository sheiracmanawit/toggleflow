# ADR 004: Feature-oriented Vue Architecture

**Status:** Accepted; migration committed in TF-24

Dashboard code is organized by product feature so components, composables, types,
tests, and feature-specific utilities remain colocated. Truly cross-feature UI and
infrastructure live in explicit shared areas. Features may depend on shared code and
documented public feature boundaries, not another feature's private internals.
