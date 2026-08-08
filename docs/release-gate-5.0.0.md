# 5.0.0 release gate

Tracking document for [issue #1590](https://github.com/bmlt-enabled/yap/issues/1590). Do not tag `5.0.0` until every automated item below is green and the manual upgrade drill is recorded.

## Automated gates (CI)

| Phase | Issue | Status | Proof |
|-------|-------|--------|-------|
| 1 | [#1575](https://github.com/bmlt-enabled/yap/issues/1575) — delete `POST /api/resetDatabase` | Done | `src/tests/Feature/ResetDatabaseTest.php` |
| 1 | [#1576](https://github.com/bmlt-enabled/yap/issues/1576) — test suite on release tags | Done | `.github/workflows/release.yml` `test` + `e2e` jobs |
| 1 | [#1577](https://github.com/bmlt-enabled/yap/issues/1577) — WebChat/WebRTC experimental + gated SMS | Done | `src/tests/Feature/WebChatWebRtcDisabledTest.php` |
| 2 | [#1578](https://github.com/bmlt-enabled/yap/issues/1578) — gender routing fix | Done | `HelplineController.php` uses `session()->get('Gender')`; `VolunteerRoutingDecisionTest` |
| 2 | [#1579](https://github.com/bmlt-enabled/yap/issues/1579) — login override seeding | Done | `AuthenticationService::initializeSessionForAuthV2()` passes `$rights[0]` |
| 3 | [#1580](https://github.com/bmlt-enabled/yap/issues/1580) — determinism groundwork | Done | Seeded RNG, fixed TZ, `zend.assertions` in CI |
| 3 | [#1581](https://github.com/bmlt-enabled/yap/issues/1581) — `FakeTwilioAccount` + `CallScenario` | Done | `src/tests/Fakes/FakeTwilioAccount.php`, `src/tests/CallScenario.php` |
| 3 | [#1582](https://github.com/bmlt-enabled/yap/issues/1582) — scenario breadth | Done | `src/tests/Feature/Scenarios/ScenarioBreadthTest.php` |
| 3 | [#1583](https://github.com/bmlt-enabled/yap/issues/1583) — hermetic suite | Done | `src/tests/Fixtures/http/`, `FakeHttp` |
| 4 | [#1584](https://github.com/bmlt-enabled/yap/issues/1584) — stop auto-migrate landmine | Done | `DatabaseMigrations` middleware gated; `UuidMigrationTest` |
| 4 | [#1585](https://github.com/bmlt-enabled/yap/issues/1585) — `yap:preflight` | Done | `src/app/Console/Commands/PreflightCommand.php` |
| 4 | [#1586](https://github.com/bmlt-enabled/yap/issues/1586) — upgrade guide + release notes | Done | `src/docs/docs/miscellaneous/upgrading-from-yap-4x-to-yap-5x.md`, `RELEASENOTES.md` |
| 4 | [#1587](https://github.com/bmlt-enabled/yap/issues/1587) — session rewrite audit | Done | `docs/session-rewrite-audit.md` |
| 4 | [#1588](https://github.com/bmlt-enabled/yap/issues/1588) — restore `throttle:api` | Done | `KernelMiddlewareTest` |
| 4 | [#1589](https://github.com/bmlt-enabled/yap/issues/1589) — Twilio signature + proxy matrix | Done | `TwilioSignatureValidationTest`, `TwilioSignatureScenarioTest` |

### Simulated-call harness (release gate acceptance)

The `#1581` harness asserts **routing decisions**, not just TwiML shape. `GenderRoutingScenarioTest` dials through the live call path and asserts `FakeTwilioAccount` received the volunteer number matching the caller's gender keypress. TwiML-only tests cannot catch the `session()->has('Gender')` regression because the prompts are byte-identical — only the dialed number changes.

Run the harness locally (requires MySQL per `.env.pipeline`):

```bash
cd src
cp .env.pipeline .env
vendor/bin/pest tests/Feature/Scenarios/
vendor/bin/pest tests/Feature/VolunteerRoutingDecisionTest.php
```

## Manual gate (before tagging)

Restore a **copy** of a real 4.5.x production database and upgrade end to end on the PHP 8.5 image:

1. Back up the database.
2. Deploy the 5.0.0 artifact.
3. Run `php artisan yap:preflight` — all checks must pass.
4. Run `php artisan migrate` manually for the UUID migration step.
5. Verify the `users` table: primary key present, ids distinct, row count preserved.
6. Log in as a service-body admin (database auth path) and confirm `override_*` settings seed in the admin session.

Record the drill date, database snapshot source, and operator in the release issue before tagging.

## Deferred to 5.1.0

See [#1590](https://github.com/bmlt-enabled/yap/issues/1590) — session-driver fidelity in CI, call-PIN table collision, SMS/webchat/`<Record>` legs in `CallScenario`, Clock abstraction, removed-route shims, `ConfigData` decode contract test, coverage floor, dead `/info` route, and promoting WebChat/WebRTC to supported.
