# ADR 002: Sanctum Dashboard Authentication

**Status:** Accepted

The first-party dashboard uses Sanctum cookie-based authentication. Client-side route
guards improve navigation but Laravel policies remain the authorization boundary.
Passport and JWTs are not used for dashboard authentication.
