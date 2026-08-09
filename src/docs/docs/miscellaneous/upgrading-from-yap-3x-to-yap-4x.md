# Upgrading from Yap 3.x to Yap 4.x

There are no special one-time migration steps between Yap 3.x and 4.x beyond a normal version upgrade: replace the application files, keep your existing `config.php` and database, and test on a staging copy first.

## Highlights in Yap 4.0

Yap 4.0 (October 2021) moved the application to the **Laravel framework** and added operator-facing improvements such as:

- Voicemail deletion in the admin portal
- Gender routing with a no-preference option
- Custom geocoding overrides (latitude/longitude without Google)
- Report formatting improvements
- Fixes for volunteer SMS routing, music on hold inheritance, and group management UI

See the [4.0.0 release notes](https://github.com/bmlt-enabled/yap/blob/main/RELEASENOTES.md#400-october-16-2021) in `RELEASENOTES.md` for the full list.

## After upgrading to 4.x

1. Confirm PHP and MySQL versions meet requirements for your target 4.x release (see [PHP requirements](../general/php-requirements)).
2. Run database migrations if prompted (`php artisan migrate`).
3. Open the admin portal and verify volunteer routing, reports, and settings.
4. When you later move to Yap 5.x, follow [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x)—that path has mandatory preflight checks and breaking changes.

## Older upgrade paths

- [Upgrading from Yap 2.x to Yap 3.x](./upgrading-from-yap-2x-to-yap-3x)
- [Upgrading from Yap 1.x to Yap 2.x](./upgrading-from-yap-1x-to-yap-2x)
