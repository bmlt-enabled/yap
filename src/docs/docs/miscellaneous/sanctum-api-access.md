# Sanctum API Access

---

Yap 5.x uses [Laravel Sanctum](https://laravel.com/docs/sanctum) bearer tokens for admin REST API authentication. The React admin SPA, scripts, CI jobs and external tools all use the same token from `POST /api/v1/login`.

Every permission check uses the token's user only: its `is_admin` flag, permissions and service bodies. The session cookie is not a credential and grants nothing. A request without a token is `401`, with or without a session cookie.

For the high-level upgrade summary, see [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) section 8.

## Obtain a token

```http
POST /api/v1/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

Successful response:

```json
{
  "status": "success",
  "token": "1|plainTextTokenString...",
  "user": { "id": 1, "username": "...", "is_admin": true, ... }
}
```

Works for BMLT-backed accounts and Yap-local database users. Optional `language` in the body sets the session language for localized API responses. The response never includes the password hash.

### BMLT accounts

- Yap checks a BMLT account with the BMLT root server on **every** login and never stores its password. A changed or disabled BMLT password takes effect at the next login.
- The account's service bodies are reloaded from BMLT at every login, and existing tokens pick up the change at that next login. A BMLT account is never a Yap admin.
- A BMLT login is refused when a local Yap account has the same username. The local account still signs in with its local password.

## Use the token

Send the token on every protected request:

```http
GET /api/v1/user
Authorization: Bearer 1|plainTextTokenString...
Accept: application/json
```

Most admin operations live under `/api/v1/*` with `auth:sanctum` middleware. OpenAPI annotations are published at `/api/v1/documentation` when generated locally (`make swagger` from the repo).

### Example: list volunteers

```bash
TOKEN="..." # from login response
curl -s -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "https://your-yap-instance/api/v1/volunteers?service_body_id=44"
```

## Verify the current user

```http
GET /api/v1/user
Authorization: Bearer ...
```

Returns the authenticated user record (id, username, `is_admin`, permissions). The SPA uses this endpoint instead of the removed `/api/v1/auth/check` from 4.x.

## Revoke tokens (logout)

```http
POST /api/v1/logout
Authorization: Bearer ...
```

Deletes **all** Sanctum tokens for that user and clears the server session. Issue a new token with `POST /api/v1/login` when you need access again.

The admin UI sign-out control clears browser storage but does not call this endpoint; long-lived automation should logout explicitly when finished.

## Token lifecycle notes

- Tokens are stored in `personal_access_tokens` and tied to the integer `users.id` primary key.
- **Username** is the stable identifier for local admin accounts in scripts—not numeric user id.
- Tokens expire `SESSION_LIFETIME` minutes (default 120) after login, whether or not they are used. After that, protected routes return `401` and you log in again.
- Each login creates a new token (`API Token` name). Old tokens remain valid until logout or manual deletion.
- Protect tokens like passwords; use HTTPS only on production hosts.

## Related topics

- [Contribute](./contribute) — local dev with ngrok and Twilio
- [Experimental Web Widgets](./experimental-web-widgets) — public widget endpoints (separate from admin Sanctum)
