---
sidebar_position: 2
---

# PHP Requirements

Yap 5.0 runs on **PHP 8.2 or newer** with **MySQL 8.0+** (or MariaDB 10.3+) and an Apache-based web server with `mod_rewrite` enabled.

Most install problems on shared Linux hosting come from **disabled PHP extensions**, not from missing Yap files. The upgrade advisor and `php artisan yap:preflight` check that required extensions are loaded.

## Required PHP extensions

| Extension | Why Yap needs it |
|---|---|
| `pdo` | Database abstraction (Laravel) |
| `pdo_mysql` | MySQL/MariaDB connectivity |
| `fileinfo` | File handling in Laravel; missing extension causes **Class 'finfo' not found** |
| `mbstring` | Multibyte strings (Laravel, localization) |
| `openssl` | HTTPS to Twilio, BMLT, and Google APIs |
| `curl` | Outbound HTTP (Guzzle, Twilio SDK) |
| `json` | API requests and responses |
| `ctype` | Character checks (Laravel) |
| `filter` | Input filtering (Laravel) |
| `hash` | Password hashing and digests |
| `session` | Admin SPA and call-flow session state |
| `tokenizer` | Laravel framework bootstrap |
| `xml` / `dom` | XML parsing in dependencies |

### Recommended (not required today)

| Extension | Why |
|---|---|
| `iconv` | Character set conversion |
| `intl` | Internationalized domain names in outbound HTTP |

## Shared Linux hosting (cPanel, Plesk, etc.)

Your provider controls which PHP extensions are enabled. Yap cannot enable them for you.

1. Open your host's **PHP version / PHP extensions** panel (often “Select PHP Version”, “PHP Extensions”, or “MultiPHP INI Editor”).
2. Select **PHP 8.2 or newer** for the domain or subdirectory where Yap is installed.
3. Enable every extension in the table above. On many hosts **`fileinfo` is off by default** — turn it on if you see `Class 'finfo' not found`.
4. Enable **`pdo_mysql`** if database connections fail.
5. Save and restart PHP-FPM or Apache if the panel offers that option.

If an extension is not listed, open a ticket with your host and ask them to enable it for your PHP SAPI (FPM or Apache module).

## Self-hosted Linux

Install the extension packages that match your PHP version, then restart PHP-FPM or Apache.

**Debian / Ubuntu** (example for PHP 8.2):

```bash
sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-fileinfo
sudo systemctl restart php8.2-fpm
```

**RHEL / Alma / Rocky** (example):

```bash
sudo dnf install php-mysqlnd php-mbstring php-xml php-curl php-fileinfo
sudo systemctl restart php-fpm
```

The official Yap Docker image (`docker/Dockerfile`) ships PHP 8.5 with `pdo`, `pdo_mysql`, and `mbstring` installed; the base `php:8.5-apache` image includes most other required extensions by default.

## Web server

- **Apache** with `mod_rewrite` enabled (required for clean URLs and the admin SPA).
- **HTTPS** with a valid TLS certificate (required by Twilio webhooks).

## Verify your server

From SSH or your host's terminal:

```bash
php -v
php -m
```

Look for `fileinfo`, `pdo_mysql`, `curl`, `mbstring`, and `openssl` in the `php -m` output.

From a browser or curl after Yap is deployed:

```bash
curl https://your-yap-host/api/v1/upgrade
```

The `checks` array includes **PHP extensions** — any missing extension is listed with remediation text.

Or run preflight before a 4.x → 5.x upgrade:

```bash
cd src
php artisan yap:preflight
```

## Common errors

| Error | Likely cause |
|---|---|
| `Class 'finfo' not found` | `fileinfo` extension disabled |
| `could not find driver` | `pdo` or `pdo_mysql` not installed |
| `Call to undefined function curl_init()` | `curl` extension missing |
| HTTP 500 on first admin login | Check `storage/logs/laravel.log` and `php -m` |

## `build.txt` is not a requirements file

Release packages include `storage/app/build.txt` containing the git commit SHA for that build. It appears in `/api/v1/upgrade` as the `build` field. It does **not** list PHP modules.
