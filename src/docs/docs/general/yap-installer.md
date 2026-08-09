# Yap Installer (First Run)

---

On a fresh install, Yap has no `config.php` yet. Until that file exists, most HTTP requests show the **Yap Installer** page instead of the IVR or admin UI.

## What you see

Visiting your Yap URL (for example `https://example.com/index.php` or `https://example.com/admin`) displays a short page titled **Yap Installer**. It explains that you must create `config.php` in the Yap root and lists the required settings:

- `title`
- `bmlt_root_server`
- `google_maps_api_key`
- `twilio_account_sid`
- `twilio_auth_token`
- `mysql_hostname`
- `mysql_username`
- `mysql_password`
- `mysql_database`

The installer is implemented in `installer.blade.php` and is shown by the `ConfigCheck` middleware when `config.php` is missing.

## Complete installation

1. Follow [Setup](./setup) to download Yap, create the database, and copy settings into `config.php`.
2. Copy `src/.env.example` to `src/.env` and set Laravel values (`TRUSTED_PROXIES`, `SESSION_DRIVER`, and so on) as described in Setup.
3. Once `config.php` exists, the installer no longer appears. Run `php artisan migrate` (or allow safe migrations on first request) and open `https://your-yap-instance/api/v1/upgrade` or the admin **System Health** page to confirm configuration.
4. Log in at `/admin` and finish Twilio webhook configuration from Setup.

The installer page links to [https://yap.bmlt.app](https://yap.bmlt.app) for full documentation.

## Related topics

- [Setup](./setup) — step-by-step first install
- [Upgrading from Yap 4.x to Yap 5.x](../miscellaneous/upgrading-from-yap-4x-to-yap-5x) — if you are upgrading an existing server instead of installing fresh
