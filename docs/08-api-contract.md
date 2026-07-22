# API Contract

## 1. Scope

This document defines the public evaluation API required by MVP 0.1. Administrative
endpoints are internal dashboard endpoints and may evolve more quickly. The public API
is versioned because SDKs and client applications will depend on it.

Base path:

```text
/api/v1
```

JSON is used for requests and responses. Clients send and accept `application/json`.

## 2. Authentication

Evaluation requests use a bearer token issued for one environment:

```http
Authorization: Bearer tf_env_<public-prefix>_<secret>
```

The exact token format may change before implementation, but it must distinguish
environment keys from user/session tokens and support lookup without storing the full
secret.

The resolved key determines the project and environment. Requests cannot override
either value.

The bearer value is an opaque API key, not a JWT or OAuth access token. The `Bearer`
scheme describes how the credential is transported in HTTP and does not imply a token
format. ToggleFlow stores only a lookup prefix and secure hash of the secret.

Laravel Passport is not part of the MVP evaluation path. See
[Authentication and API Key Decision](11-authentication-and-api-key-decision.md) for
the alternatives and rationale.

## 3. Evaluate One Flag

```http
GET /api/v1/flags/{flagKey}
Authorization: Bearer <environment-api-key>
Accept: application/json
```

Successful response:

```json
{
  "data": {
    "key": "new-checkout",
    "value": true,
    "reason": "STATIC"
  }
}
```

The `value` field is intentionally named generically so the API can support string or
numeric variations in a future version. MVP 0.1 returns only booleans.

### Result Reasons

| Reason | Meaning |
| --- | --- |
| `STATIC` | The configured boolean value was returned. |
| `FLAG_NOT_FOUND` | No active flag exists for the supplied key. |
| `FLAG_ARCHIVED` | The flag is archived and not eligible for evaluation. |
| `CONFIGURATION_MISSING` | The flag has no valid state for the environment. |

For MVP 0.1, missing or unavailable flags return `false` with HTTP 200 so client code
can fail safely and still inspect the reason:

```json
{
  "data": {
    "key": "unknown-flag",
    "value": false,
    "reason": "FLAG_NOT_FOUND"
  }
}
```

This behavior should be revisited when SDKs introduce caller-defined defaults. The
eventual evaluator contract should prefer a default supplied by application code.

## 4. Errors

Authentication and malformed-request failures use an error envelope:

```json
{
  "error": {
    "code": "INVALID_API_KEY",
    "message": "The supplied API key is invalid or has been revoked."
  }
}
```

Recommended statuses:

| Status | Code | Usage |
| --- | --- | --- |
| 401 | `MISSING_API_KEY` | No bearer token was provided. |
| 401 | `INVALID_API_KEY` | The token is unknown, malformed, or revoked. |
| 404 | `ENDPOINT_NOT_FOUND` | The requested API route does not exist. |
| 422 | `INVALID_REQUEST` | Valid JSON or request parameters are required. |
| 429 | `RATE_LIMITED` | The client exceeded the allowed request rate. |
| 500 | `INTERNAL_ERROR` | An unexpected server failure occurred. |

Internal errors must not reveal stack traces, SQL, hashes, or secret material.

## 5. Future-compatible Evaluation Request

Targeting rules will eventually require a context-bearing endpoint, for example:

```http
POST /api/v1/evaluations
Authorization: Bearer <environment-api-key>
Content-Type: application/json
```

```json
{
  "flag_key": "new-checkout",
  "default_value": false,
  "context": {
    "targeting_key": "user-123",
    "email": "developer@example.com",
    "attributes": {
      "plan": "pro"
    }
  }
}
```

This endpoint is documented as a direction, not an MVP requirement. It should not be
implemented until targeting or caller-defined defaults are scheduled.

## 6. API Stability Rules

- Existing fields are not removed or renamed within `/api/v1`.
- New optional fields may be added.
- New reason codes may be added; clients must handle unknown reasons safely.
- Authentication failures never reveal whether a particular project, environment, or
  flag exists.
- Date-time values, when added, use ISO 8601 UTC strings.
- Request IDs should be returned in headers when observability is introduced.

## 7. Example Integration

Framework-neutral pseudocode:

```text
function enabled(flagKey, fallback = false):
    try:
        response = GET TOGGLEFLOW_URL + "/api/v1/flags/" + flagKey
        response.header("Authorization", "Bearer " + TOGGLEFLOW_API_KEY)
        return response.data.value
    catch network_or_server_error:
        return fallback
```

Client applications must always retain a local fallback so ToggleFlow outages do not
make the consuming application unusable. Official SDKs will standardize timeouts,
caching, and fallback behavior in a later release.

## 8. API Key Operational Guidance

- Use different keys for Development, Staging, and Production.
- Never embed server-side keys into public browser bundles.
- Revoke suspected keys immediately and create a replacement.
- Display the complete secret only at creation time.
- Redact keys in logs and error reports.
- Use clear key names such as `checkout-api-production` to support rotation.
- Rotate without downtime by issuing a replacement, deploying it to the client,
  verifying evaluation, and then revoking the old key.
