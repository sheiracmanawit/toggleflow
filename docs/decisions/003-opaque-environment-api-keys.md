# ADR 003: Opaque Environment API Keys

**Status:** Accepted

Evaluation uses opaque keys scoped to exactly one environment. Plaintext is displayed
only once, only a hash is stored, and credentials may be revoked. The credential—not
request data—determines project and environment.
