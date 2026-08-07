# Session rewrite audit (4.5.1 → main)

Line-by-line review of every `$_SESSION` reference in `src/app/` on tag **4.5.1** (101 token occurrences across **89 lines** in **18 files**). Compared against **main** at the time of this audit branch.

## Summary

| Verdict | Count |
|---------|------:|
| correct | 74 |
| bug (fixed in prior PRs) | 2 |
| bug (fixed in this PR) | 3 |
| removed (intentional refactor) | 8 |
| removed (dead code / unused session write) | 2 |

**Hazard classes**

| Class | Status |
|-------|--------|
| 1. `has()` in value position | Closed — CI guard added (`src/scripts/session-has-value-position-guard.sh`) |
| 2. Dropped array subscripts | No `$_SESSION[key][0]` sites in scope |
| 3. `??` coalesce sites | 4 sites — 2 were bugs (Gender, auth `[0]`); both fixed |
| 4. Type drift | No loose string comparisons on session values in the 18 files |
| 5. Objects in session | `VolunteerRoutingParameters`, `Volunteer`, `volunteers_randomized` still stored as objects — flag for CI fidelity follow-up |
| 6. `SettingsService::has()` truthy-default trap | Audited — see [Settings `has()` audit](#settingsservicehas-audit) |
| 7. `SessionKey` global middleware | Login regenerates session (`AuthController:80`); call `ysk` is separate from admin Sanctum tokens |

## `getCallTokenForForwarding` / SHAKEN-STIR CallToken

**Verdict: accidentally lost during the rewrite; restored in this PR.**

4.5.1 stored inbound `CallToken` in `CallSession` middleware (`$_SESSION['call_token']`), read it via `CallService::getCallTokenForForwarding()`, and passed it as `callToken` on volunteer outdial when `forced_caller_id` was disabled. All three pieces were removed on main with no replacement.

`forced_caller_id` itself was **not** lost — `CallService::getOutboundDialingCallerId()` still reads service-body config. Only the SHAKEN/STIR forwarding token regressed.

`stir_verstat` session writes (CallSession 66–67) were never read anywhere; however, direct `StirVerstat` **request** logging in `CallFlowController` was also removed on main (see row 4.5.1 CallFlowController:81 area). Filed separately as [#1601](https://github.com/bmlt-enabled/yap/issues/1601).

## Site-by-site table

| File | 4.5.1 line | Main line | Verdict | Notes |
|------|-----------:|----------:|---------|-------|
| RootServerServiceBodiesController | 37 | — | removed | Session auth mutation removed; endpoint now uses authenticated user context |
| RootServerServiceBodiesController | 38 | — | removed | Same |
| UserController | 51 | 190 | correct | `session()->get('username')` |
| CallFlowController | 188 | 185 | correct | `!session()->has('override_service_body_id')` |
| CallFlowController | 194 | 191 | correct | `session()->has('override_service_body_id')` |
| CallFlowController | 377 | 374 | correct | `session()->put('Gender', $gender)` |
| CallFlowController | 624 | 630 | correct | `session()->get('initial_webhook')` |
| HelplineController | 72 | 72 | correct | `session()->has("override_service_body_id")` |
| HelplineController | 76 | 76 | correct | `session()->get('Address') ?? …` |
| HelplineController | 133 | 133 | correct | `session()->has("override_service_body_id")` in boolean chain |
| HelplineController | 154 | 154 | correct | `!session()->has('Gender')` |
| HelplineController | 156 | 156 | correct | `session()->put("Address", $address)` |
| HelplineController | 302 | — | removed | Dead read of `ActiveVolunteer` on participant-join (assigned, never used) |
| HelplineController | 303 | — | removed | Same |
| HelplineController | 348 | 344 | correct | `session()->put('master_callersid', …)` |
| HelplineController | 443 | 439 | correct | `session()->get('Gender') ?? …` (was bug with `has()` — fixed #1594) |
| HelplineController | 446 | 442 | correct | `session()->put("volunteer_routing_parameters", …)` |
| HelplineController | 478 | 468 | correct | `!session()->has('ActiveVolunteer')` |
| HelplineController | 479 | 469 | correct | `session()->put('ActiveVolunteer', …)` |
| HelplineController | 483 | 473 | correct | `session()->put('no_answer_max', …)` |
| HelplineController | 484 | 474 | correct | `session()->put('voicemail_url', …)` |
| HelplineController | 486 | 476 | correct | `session()->put('no_answer_max', 0)` |
| SessionController | 11 | — | removed | Moved to `Api/V1/Admin/SessionController` (Laravel `Session` facade) |
| SessionController | 15 | 34 | correct | `foreach (Session::all() …)` |
| SessionController | 19 | 36 | correct | `Session::forget($key)` |
| VoicemailController | 107 | 107 | correct | `session()->has("volunteer_routing_parameters")` |
| VoicemailController | 108 | 108 | correct | `session()->get("volunteer_routing_parameters")` |
| CallSession | 40 | 38 | correct | `!$request->session()->has('override_service_body_id')` |
| CallSession | 52 | 52 | correct | `!$request->session()->has('call_state')` |
| CallSession | 53 | 53 | correct | `$request->session()->put('call_state', …)` |
| CallSession | 57 | 57 | correct | `!$request->session()->has('initial_webhook')` |
| CallSession | 59 | 60 | correct | `$request->session()->put('initial_webhook', …)` |
| CallSession | 63 | 64 | correct | CallToken guard — restored in this PR |
| CallSession | 64 | 65 | correct | `$request->session()->put('call_token', …)` — restored in this PR |
| CallSession | 66 | — | removed | `stir_verstat` session write was never read; request-side logging also lost — #1601 |
| CallSession | 67 | — | removed | Same |
| CallSession | 73 | 66 | correct | `$request->session()->put($key, $value)` for `override_*` |
| AuthenticationRepository | 33 | — | removed | BMLT cookie session now set in `RootServerService::authenticate()` |
| AuthenticationRepository | 65 | — | removed | Username caching moved to `RootServerService::getLoggedInUsername()` |
| AuthenticationRepository | 70 | — | removed | Same |
| AuthenticationRepository | 71 | — | removed | Same |
| AuthenticationRepository | 73 | — | removed | Same |
| AuthenticationService | 27 | 46–53 | correct | `session()->put([…])` batch in `initializeSessionForAuthV2` |
| AuthenticationService | 28 | 46–53 | correct | Same |
| AuthenticationService | 29 | 46–53 | correct | Same |
| AuthenticationService | 30 | 46–53 | correct | Same |
| AuthenticationService | 31 | 46–53 | correct | Same |
| AuthenticationService | 32 | 46–53 | correct | Same |
| AuthenticationService | 33 | 55–56 | correct | `session()->put('auth_service_bodies_rights', $rights)` |
| AuthenticationService | 34 | 59 | correct | `$rights[0]` to `setConfigForService` (was bug dropping `[0]` — fixed #1596) |
| AuthenticationService | 38 | 66–69 | correct | V1 init `session()->put([…])` |
| AuthenticationService | 39 | 66–69 | correct | Same |
| AuthenticationService | 42 | 84 | correct | `session()->put('auth_service_bodies_rights', $rights)` |
| AuthenticationService | 56 | — | removed | `verify()` refactored to Sanctum middleware |
| AuthenticationService | 57 | — | removed | Same |
| AuthenticationService | 69 | — | removed | `logout()` V1 path refactored |
| AuthenticationService | 70 | — | removed | Same |
| AuthorizationService | 15 | 15 | correct | `session()->get("auth_service_bodies_rights") ?? null` |
| AuthorizationService | 32 | 34–35 | correct | `session()->has/get('auth_is_admin')` |
| AuthorizationService | 33 | 37 | correct | `session()->has/get('auth_permissions')` with bitmask |
| AuthorizationService | 38 | 58 | correct | `session()->has/get('auth_is_admin')` in `isTopLevelAdmin` |
| CallService | 128 | 123–130 | correct | `getCallTokenForForwarding()` restored — `session()->get('call_token')` |
| ConfigService | 76 | 69 | correct | `session()->put($key, $value)` for override keys |
| HttpService | 43 | 43 | correct | Ternary with `session()->has/get('bmlt_auth_session')` |
| RootServerService | 89 | 89 | correct | `session()->has('auth_mechanism')` |
| RootServerService | 90 | 90 | correct | `session()->get('auth_mechanism')` |
| RootServerService | 120 | 120 | correct | V2 admin branch |
| RootServerService | 122 | 122 | correct | V2 non-admin branch |
| RootServerService | 124 | 124 | correct | `session()->get('auth_service_bodies')` |
| SessionService | 27 | 28 | correct | Skip Twilio creds override when `call_state` present |
| SessionService | 30 | 31 | correct | `session()->put("override_" . $item, …)` |
| SettingsService | 153 | 166 | correct | `foreach (session()->all() …)` |
| SettingsService | 205 | 241 | correct | `session()->has/get("override_" . $name)` in `get()` |
| SettingsService | 206 | 242 | correct | Same |
| SettingsService | 225 | 261 | correct | `session()->has("override_" . $name)` in `source()` |
| SettingsService | 318 | 354 | correct | `session()->put("override_word_language", …)` |
| SettingsService | 319 | 355 | correct | `session()->put("override_gather_language", …)` |
| SettingsService | 320 | 356 | correct | `session()->put("override_language", …)` |
| TwilioService | 50 | 51–52 | correct | `session()->get/put('no_answer_count')` |
| TwilioService | 51 | 55 | correct | Compare to `session()->get('no_answer_max')` |
| TwilioService | 52 | 56 | correct | `session()->get('master_callersid')` |
| TwilioService | 54 | 61 | correct | `session()->get('master_callersid')` update |
| TwilioService | 56 | 63 | correct | `session()->get('voicemail_url')` |
| TwilioService | 66 | 73 | correct | `!session()->has('is_mobile')` |
| TwilioService | 75 | 85 | correct | `session()->put('is_mobile', …)` |
| TwilioService | 78 | 88 | correct | `session()->get('is_mobile')` |
| VolunteerService | 138 | 134 | correct | `!session()->has('volunteers_randomized')` |
| VolunteerService | 140 | 136 | correct | `session()->put('volunteers_randomized', …)` |
| VolunteerService | 143 | 139 | correct | `session()->get('volunteers_randomized')` |

## SettingsService::has() audit

`SettingsService::has()` returns `!is_null($this->get($name))`, and `get()` falls back to allowlist defaults. It does **not** mean “configured” or “non-empty”.

| Site | Means “configured”? | Safe? | Notes |
|------|---------------------|-------|-------|
| `VoicemailService:65` `smtp_alt_port` | No | needs-test | Empty default still truthy; only gates optional alt port |
| `UpgradeService:41` loop | No | correct | Checks unset keys against required list |
| `UpgradeService:113` `smtp_host` | No | correct | Nested check uses `get()` for values |
| `UpgradeService:121` `mysql_hostname` | **Yes (buggy)** | bug | Always true when default exists — migration runs unconditionally |
| `UpgradeService:146` `exclude_errors_on_login_page` | No | correct | Feature flag with `get()` for value |
| `TimeZoneService:19` `timezone_default` | No | correct | Ternary chooses between settings |
| `RootServerService:177` `ignore_formats` | No | correct | Feature presence |
| `RootServerService:307` `alt_auth_method` | No | correct | Paired with `get()` |
| `CallService:164` `language_selections` | No | correct | |
| `CallService:215` `speech_gathering` | No | correct | Paired with `json_decode(get())` |
| `CallService:234–240` SMS bias settings | No | correct | elseif chain |
| `SmsBlackhole:31` | No | correct | Paired with `get()` |
| `DatabaseMigrations:30` `mysql_hostname` | **Yes (buggy)** | bug | Same trap as UpgradeService — fires on every request |
| `CallBlocklist:31` | No | correct | Paired with `get()` |
| `WebRtcCallController:49` | No | correct | **Double-checks** with `get('webrtc_enabled')` |
| `VoicemailController:53` promptset | No | correct | Dynamic prompt key |
| `VoicemailController:130` `smtp_host` | No | correct | Email enabled path |
| `HelplineController:87,114` `fallback_number` | No | correct | Uses `get()` for dial string |
| `CallFlowController` (many) | No | correct | All paired with `get()` / `json_decode(get())` except presence checks |
| `WebRtcController:70,155` | No | correct | Double-checks with `get()` |
| `WebChatController:544` | No | correct | Double-checks with `get()` |

**ValidateTwilioSignature** correctly uses `empty($this->settings->get('twilio_auth_token'))` instead of `has()`.

Known follow-ups: `DatabaseMigrations` and `UpgradeService` `mysql_hostname` checks — [#1602](https://github.com/bmlt-enabled/yap/issues/1602).

## CI guard

```bash
cd src && bash scripts/session-has-value-position-guard.sh
```

Wired into `.github/actions/lint/action.yml`. Rejects `session()->has()` used as a value (assignment, `??` RHS, etc.) while allowing `if` / `elseif` / `&&` / `||` boolean contexts.

## Tests added

- `tests/Feature/CallTokenForwardingTest.php` — behavioral coverage for CallToken storage and volunteer outdial forwarding (including forced-caller-ID suppression).
