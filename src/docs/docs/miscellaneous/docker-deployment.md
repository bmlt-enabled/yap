---
title: Docker Deployment
sidebar_position: 8
---

# Docker Deployment

Yap ships a PHP 8.5-based Docker image for self-hosters who prefer containers over manual Apache/PHP setup.

## Quick start

1. Mount your `config.php` into the container (or bake it into your own image layer).
2. Expose port 80 from the container to your reverse proxy.
3. Set `TRUSTED_PROXIES` when the container sits behind nginx, Traefik, or another proxy — Twilio signature validation needs the public URL Twilio signed.
4. Use `SESSION_DRIVER=file` (or `redis`). Do **not** set `SESSION_DRIVER=database`; Yap's `sessions` table stores call PINs, not Laravel sessions.

## Environment variables

See `.env.example` in the Yap `src/` folder for Laravel-level settings (`TRUSTED_PROXIES`, `SESSION_DRIVER`, `SANCTUM_STATEFUL_DOMAINS`, `TWILIO_DISABLE_SIGNATURE_VALIDATION`). Database credentials and most Yap settings still live in `config.php`.

## PHP extensions

The image installs `pdo`, `pdo_mysql`, and `mbstring`. Other required extensions ship with the base `php:8.5-apache` image. See [PHP requirements](../general/php-requirements) for the full list and how to verify them.

## Database

Point `config.php` at your MySQL 8.0+ or MariaDB 10.3+ instance. Run `php artisan yap:preflight` and `php artisan migrate` from a shell in the container before sending live Twilio traffic at a 4.x → 5.x upgrade.

## Upgrading

Follow [Upgrading from Yap 4.x to Yap 5.x](./upgrading-from-yap-4x-to-yap-5x) — back up the database, run preflight, then migrate manually during a maintenance window.
