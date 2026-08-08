# 5.0.0 documentation and application cleanup audit

Audit date: 2026-08-08. Tracks follow-up work for the 5.0.x release line. GitHub issue: [#1611](https://github.com/bmlt-enabled/yap/issues/1611).

## Executive summary

The 5.0.0 operator upgrade path (`upgrading-from-yap-4x-to-yap-5x.md`, `yap:preflight`, release notes) is strong. Gaps cluster around **stale platform requirements**, **doc contradictions**, **broken admin SPA endpoints**, **settings-to-docs link drift**, and **wiki-era URLs** still embedded in the app.

| Severity | Count | Examples |
|----------|------:|----------|
| Critical | 5 | Dead `/info` route (500), `Users.js` dead auth endpoint, `upgrading.md` UUID contradiction |
| Important | ~20 | Stale MySQL version, missing `.env.example`, 30 settings without doc links |
| Cleanup | ~10 | Orphaned Vite config, stale `TODO.md`, Docusaurus URL mismatch |

---

## Critical — fix before or immediately after 5.0.0 tag

### C1. Dead `/info` route returns HTTP 500

- **Route:** `src/routes/web.php:31` → `CallFlowController@info`
- **Problem:** `info()` does not exist in `CallFlowController.php`. Any Twilio app still pointing at `/info` gets a 500.
- **Fix:** Remove the route or restore a handler. Add a feature test (404 or valid TwiML).

### C2. Users page calls removed API endpoint

- **File:** `src/resources/js/pages/Users.js:57`
- **Problem:** Loads current user from `/api/v1/auth/check`, which is not registered. Sanctum endpoint is `GET /api/v1/user` (used correctly in `Layout.js`, `Dashboard.js`, `Reports.js`).
- **Impact:** `currentUsername` and `isAdmin` never populate; delete-self guard and Admin column are unreliable.
- **Fix:** Switch to `apiClient.get('/api/v1/user')`. Add e2e test for "cannot delete self."

### C3. Settings "Clear Database Cache" fails auth

- **File:** `src/resources/js/pages/Settings.js:44–52`
- **Problem:** Uses raw `fetch('/api/v1/cache')` without bearer token. Backend requires `auth:sanctum`.
- **Fix:** Use `apiClient.post('/api/v1/cache')`.

### C4. No `.env.example`

- **Problem:** `composer.json` post-install copies `.env.example` → `.env`, but no `.env.example` exists. Only `.env` (local) and `.env.pipeline` (2 lines) are present.
- **Fix:** Add `src/.env.example` documenting `APP_*`, `SESSION_DRIVER`, `TRUSTED_PROXIES`, `TWILIO_DISABLE_SIGNATURE_VALIDATION`, `GOOGLE_MAPS_API_KEY`, `SANCTUM_STATEFUL_DOMAINS`, etc.

### C5. Doc contradiction: UUID migration auto-runs vs blocked

- **File:** `src/docs/docs/miscellaneous/upgrading.md:10`
- **Says:** "destructive UUID migration that runs automatically on the first HTTP request"
- **Contradicts:** `upgrading-from-yap-4x-to-yap-5x.md` §2, `DatabaseMigrations` middleware, `RELEASENOTES.md` (manual `php artisan migrate` required; HTTP 503 gate on destructive migration).
- **Fix:** Rewrite `upgrading.md` to match the 4.x→5.x guide.

---

## Important — 5.0.x follow-up

### Documentation accuracy

| Item | File | Fix |
|------|------|-----|
| Homepage says MySQL 5.7+ | `src/docs/docs/index.md:11` | Update to MySQL 8.0+ / MariaDB 10.3+ |
| No 5.0 upgrade warning | `README.md` | Add banner linking to upgrade guide + `yap:preflight` |
| Setup omits Twilio auth token requirement | `src/docs/docs/general/01-setup.md` | Stress empty token = HTTP 403 on every call in 5.0 |
| Setup step 9 migration wording | `src/docs/docs/general/01-setup.md:36` | Clarify safe migrations may auto-run; UUID step is manual |
| Configuration precedence references removed route | `src/docs/docs/general/configuration-precedence.md` | `/admin/settings.php` → `/admin` Settings page |
| Empty 3.x→4.x upgrade page | `src/docs/docs/miscellaneous/upgrading-from-yap-3x-to-yap-4x.md` | Add content or redirect |
| Thin reports doc | `src/docs/docs/general/reports.md` | Document metrics summary, SMS metrics, dialback CDR fields |
| Thin volunteer routing doc | `src/docs/docs/helpline/volunteer-routing.md` | Add blasting, cycling, responder, groups (or link to video) |
| Thin logging doc | `src/docs/docs/miscellaneous/loggingdebugging.md` | Mention `APP_DEBUG`, log location, upgrade advisor |
| No Docker deployment guide | — | Add `miscellaneous/docker-deployment.md` |
| No experimental widgets page | — | Add `miscellaneous/experimental-web-widgets.md` (even if "do not use") |
| `users.md` raw SQL guidance | `src/docs/docs/general/users.md` | Note UUID `id`, prefer Admin UI Users page |

### Stale URLs in application code

| Item | File | Current | Should be |
|------|------|---------|-----------|
| Installer docs link | `installer.blade.php:51` | `bmlt.app/yap` | `yap.bmlt.app` |
| Call handling wiki links | `CallHandlingDialog.js:278,304` | `bmlt.app/yap/#configurationprecedence` | `yap.bmlt.app/general/configuration-precedence/` |
| Upgrade advisor CDR link | `UpgradeService.php:109` | GitHub wiki | Docusaurus reports page |
| RELEASENOTES wiki link | `RELEASENOTES.md:306` | GitHub wiki | `/helpline/custom-extensions` |
| Blog post wiki link | `src/docs/blog/2021-08-01-merging-yap-post/index.md` | GitHub wiki | `/general/configuration-precedence` |

### Settings ↔ documentation alignment

| Item | File | Fix |
|------|------|-----|
| `pronunciations` typo | `SettingsService.php:59` | `'descriptions'` → `'description'` (doc link never renders) |
| `digit_map_search_type` wrong doc | `SettingsService.php:25` | `/helpline/custom-extensions/` → `/general/menu-options` |
| ~30 settings with empty `description` | `SettingsService.php` | Prioritize: `call_routing_filter`, `sms_bias_bypass`, `time_format`, `timezone_default`, `voicemail_playback_grace_hours`, `word_language` |
| WebChat/WebRTC blank docs | `SettingsService.php:89–102` | Intentional for 5.0.0; add when promoting in 5.1.0 |

### Dead code and legacy shims

| Item | File | Recommendation |
|------|------|----------------|
| `AuthController::logout()` / `rights()` | `AuthController.php:125–137` | Dead methods; routes removed. Delete or wire `POST /api/v1/logout`. |
| `UpgradeAdvisorController::version()` | `UpgradeAdvisorController.php:33–93` | Unreachable; `/api/v1/version` is a closure in `routes/api.php`. |
| SPA sign-out | `App.js:119–123` | Clears localStorage only; does not revoke Sanctum tokens. |
| `EventId::VOICEMAIL_PLAYBACK` | `EventId.php:21` | Marked dead; `voicemail_playback_grace_hours` still in Settings. |

### OpenAPI / API documentation

| Item | Status | Fix |
|------|--------|-----|
| WebChat paths in code but missing from `api-docs.json` | Stale spec | Regenerate with `make swagger` and commit |
| Swagger UI unauthenticated in production | `config/l5-swagger.php:66–70` | Consider restricting middleware |
| `GET /api/v1/upgrade`, `GET /api/v1/version` | Undocumented | Add to OpenAPI or document as operator-only |
| `CONTRIBUTE.md` API docs path | Wrong | Says `/api/documentation`; actual is `/api/v1/documentation` |

### Environment variables without operator docs

| Variable | Used in | Documented in |
|----------|---------|---------------|
| `TRUSTED_PROXIES` | `config/trustedproxy.php` | Upgrade guide only |
| `TWILIO_DISABLE_SIGNATURE_VALIDATION` | `config/twilio.php` | Upgrade guide only |
| `SESSION_DRIVER` | `config/session.php` | Upgrade guide only |
| `GOOGLE_MAPS_API_KEY` | `SettingsService.php` | Code only |
| `SANCTUM_STATEFUL_DOMAINS` | `config/sanctum.php` | Not documented; default may be malformed (`127.0.0.1:8001::1`) |
| `E2E_TESTING` | `RouteServiceProvider.php` | Not documented |

### Doc site configuration

| Item | File | Fix |
|------|------|-----|
| `url` mismatch | `docusaurus.config.js:9` | `yapdocs.com` vs CNAME `yap.bmlt.app` |
| `editUrl` wrong path | `docusaurus.config.js` | `docs/` → `src/docs/docs/` |
| Docusaurus version in README | `src/docs/README.md` | Says Docusaurus 2; project uses 3.9.x |

### Features in code with weak or no documentation

| Feature | Doc status |
|---------|------------|
| Volunteer groups | No dedicated page |
| Call blasting / cycling strategies | Video only (`call-blasting-and-other-call-strategies-explained.mdx`) |
| Responder option | Not explained for operators |
| Yap installer | No doc page |
| `yap:preflight` | Upgrade docs only; not in setup flow |
| Sanctum API auth | Upgrade guide §8 only; no consumer guide |
| `public/widget-loader.js` embed | No public embed guide |
| `widget-demo.html` | Exists but not linked from docs site |

---

## Cleanup — nice-to-have

| Item | File | Note |
|------|------|------|
| Orphaned Vite config | `vite.config.js` | References non-existent `resources/js/app.js`; Mix is active |
| Duplicate `@twilio/voice-sdk` | `package.json` | Listed twice |
| Placeholder Docusaurus page | `src/docs/src/pages/markdown-page.md` | Unused template |
| Duplicate contribute docs | `CONTRIBUTE.md` vs `miscellaneous/contribute.md` | Consolidate |
| Stale `TODO.md` | Root | Items 1411, 1399 shipped; language-on-login exists |
| Settings 📖 on empty docs | `Settings.js` | Hide link when `setting.docs` is falsy |
| `digit_map_search_type` trailing slash | `SettingsService.php:25` | Cosmetic |
| Pig Latin i18n typo | `Localizations.php` | `'ocumentationday'` |
| Internal docs not on yap.bmlt.app | `docs/session-rewrite-audit.md`, `docs/release-gate-5.0.0.md` | Add `docs/README.md` explaining internal vs public |
| `.env.pipeline` too minimal | 2 lines only | Expand or fix release-gate local test instructions |

### TODO/FIXME → tickets

| File | Line | Topic |
|------|------|-------|
| `HelplineController.php` | 75, 303 | Gender-routing address session debt; per-volunteer timeout |
| `VolunteerRoutingHelpers.php` | 23 | Gender + language routing interaction |
| `ServiceBodyCallHandling.php` | 18 | Encapsulate `volunteer_routing_redirect_id` |
| `GeocodingService.php` | 45 | API key failure handling |
| `ReportsRepository.php` | 118 | Multi–service-body report option |
| `VoicemailCompleteTest.php` | 679, 701–702 | Email subject translation |

---

## Prioritized work plan

### Sprint 1 — pre/post 5.0.0 tag (critical)

1. Fix `upgrading.md` UUID migration wording (C5)
2. Fix `Users.js` auth endpoint (C2)
3. Fix Settings cache clear auth (C3)
4. Remove or restore `/info` route (C1)
5. Add `src/.env.example` (C4)

### Sprint 2 — 5.0.x polish (important docs)

6. Update `index.md` and `README.md` platform requirements
7. Fix stale URLs in app code (installer, CallHandlingDialog, UpgradeService)
8. Fix `pronunciations` typo and `digit_map_search_type` doc path
9. Amend `01-setup.md` for Twilio auth token + migration behavior
10. Fix `configuration-precedence.md` admin route reference

### Sprint 3 — operator doc breadth

11. Docker deployment page
12. Expand `reports.md` and volunteer routing docs
13. Experimental WebChat/WebRTC page
14. Regenerate and commit `api-docs.json`
15. Fix Docusaurus config (`url`, `editUrl`)

### Sprint 4 — 5.1.0 prep

16. Full WebChat/WebRTC operator docs when promoted
17. Public Sanctum API consumer guide
18. React admin walkthrough (operator-facing)
19. Wiki → Docusaurus migration (scrub remaining wiki links)
20. Dead code removal (`AuthController::logout`, `UpgradeAdvisorController::version`)
