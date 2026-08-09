### Getting it up and running.

1. Clone this repository.
2. Run `npm install`. 
3. Run `composer install`
3. Run `make bundle`. 
4. Run `make serve`. 
5. Browse to http://localhost:8000/yap (not 127.0.0.1) in your web browser.

#### Logging in to the admin interface

1. Browse to http://localhost:8000/yap/admin (not 127.0.0.1)
2. Create an admin user in the database

```sql
INSERT INTO users (name, username, password, permissions, is_admin) VALUES ('admin', 'admin', SHA2('admin', 256), 0, 1);
```

3. Log with username: `admin`, password: `admin`.

### Testing

After cloning, run `make test`. The test suite is hermetic — it makes no network
calls, so no API keys or other secrets are needed. Outbound HTTP is stubbed from
fixtures in `src/tests/Fixtures/http/`, and `Http::preventStrayRequests()` fails
any test that tries to reach the network. See `src/tests/Fixtures/http/README.md`
if you need to add or refresh a fixture.

To run code coverage, you can run `make coverage`.

### Seeing 500 errors during tests.

Use `$this->withoutExceptionHandling()` in your test classes to see the underlying 500 error during your tests.

### Docs

1. Go to `cd src/docs`
2. Run `npm run start`

You should be able to see the documents update as you edit files at http://localhost:3000.

To test building the docs run:

```shell
npm run docusaurus build
```

### Preflight checks (developers)

Operators verify configuration through `GET /api/v1/upgrade` or the admin **System Health** page — no shell access required.

For local development and CI, you can run the same blocking checks from the shell:

```shell
cd src
php artisan yap:preflight
```

This exits non-zero when any blocking check fails. It is useful before cutting a release or testing an upgrade against a copy of production data.

### API Docs (WIP)

This only works locally right now.

1. Run `make swagger`.
2. Browse to `/api/v1/documentation`
