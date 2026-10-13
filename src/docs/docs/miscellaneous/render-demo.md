---
title: Hosted Demo on Render (free tier)
sidebar_position: 11
---

# Hosted Demo on Render (free tier)

The repository ships a [Render Blueprint](https://render.com/docs/infrastructure-as-code) (`render.yaml` at the repo root) that deploys yap as a Docker web service on Render's **free** plan. It is meant as a hosted target for the [Call/SMS Simulator](./simulator) and as a public demo or staging instance. It is **not** a way to run a live helpline.

## What it deploys

- One web service built from `docker/Dockerfile.render`: PHP 8.5 + Apache, no xdebug, Composer dependencies and frontend assets built into the image, OPcache on, Apache listening on Render's `$PORT` (10000).
- On every boot the entrypoint (`docker/render/entrypoint.sh`):
  1. renders `config.php` from environment variables,
  2. runs `php artisan migrate --force` (retrying while a cold database wakes up),
  3. builds the config, route, and view caches,
  4. starts Apache in the foreground.
- Health check: `GET /ping`.

## Free-tier caveats

- **Cold starts.** A free service sleeps after 15 minutes without traffic. The next request waits ~30–60 seconds while it boots, migrates, and warms up. Twilio gives up on a webhook after 15 seconds, so **do not point a real Twilio number at this instance**. Use it with the simulator (which waits) or for clicking around the admin UI.
- **Ephemeral filesystem.** Nothing written to disk survives a restart or deploy. The Blueprint is set up for that: Laravel sessions are stored in the database (`SESSION_DRIVER=database`, `SESSION_TABLE=laravel_sessions`), logs go to stderr (`LOG_CHANNEL=stderr`, visible in the Render log viewer), `config.php` is regenerated on each boot, and the file cache is only a cache.
- **512 MB RAM, 0.1 CPU.** Fine for a demo; expect slow responses under load.
- **No database.** Render's free database is Postgres, and yap needs MySQL. Use an external MySQL-compatible database (below).
- **No cron or workers.** yap does not need a scheduler or queue worker for the call, SMS, or admin flows, so nothing is lost here.

## 1. Create a free MySQL-compatible database

Either works. Both require TLS, which the image enables by default.

**TiDB Cloud Serverless**

1. Create a Serverless cluster at [tidbcloud.com](https://tidbcloud.com).
2. Under **Connect**, note the host, port (`4000`), user (`<prefix>.root`), and generate a password.
3. Create a database, e.g. `CREATE DATABASE yap;`.
4. TiDB's certificate is signed by a public CA, so the system CA bundle in the image is enough. No CA setting is needed.

**Aiven for MySQL (free plan)**

1. Create a free MySQL service at [aiven.io](https://aiven.io).
2. Note the host, port, user (`avnadmin`), password, and database (`defaultdb`, or create `yap`).
3. Download the service's **CA certificate**. Aiven signs with its own CA, so paste the contents of `ca.pem` into `MYSQL_SSL_CA_PEM`.

## 2. Deploy the Blueprint

1. Click [![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/bmlt-enabled/yap), or in Render choose **New → Blueprint** and select the yap repository (or your fork). Render reads `render.yaml`.
2. Fill in the values Render prompts for (these are `sync: false` in the Blueprint, so they live only in the dashboard and are never committed):

   | Variable | Value |
   |---|---|
   | `MYSQL_HOSTNAME`, `MYSQL_PORT`, `MYSQL_USERNAME`, `MYSQL_PASSWORD`, `MYSQL_DATABASE` | From step 1 |
   | `MYSQL_SSL_CA_PEM` | Aiven's `ca.pem` contents. Leave empty for TiDB. |
   | `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` | Any values for fake mode; the auth token is what the simulator signs requests with. [Twilio's test credentials](https://www.twilio.com/docs/iam/test-credentials) work. |
   | `GOOGLE_MAPS_API_KEY` | Optional for the simulator (it pins locations itself); needed for real geocoding. |

3. Apply. The first build takes a few minutes. When the deploy is live, `https://<service>.onrender.com/ping` returns `PONG` and the admin UI is at `/admin`.

### Environment variables set by the Blueprint

| Variable | Value | Why |
|---|---|---|
| `APP_KEY` | generated | The entrypoint adds Laravel's `base64:` prefix. |
| `APP_ENV` | `demo` | Must not be `production`, or `TWILIO_FAKE_MODE` is ignored. |
| `TWILIO_FAKE_MODE` | `true` | Outbound Twilio API calls are recorded in the database instead of sent. |
| `SESSION_DRIVER` / `SESSION_TABLE` | `database` / `laravel_sessions` | Sessions survive restarts. yap's own `sessions` table holds call PINs, so Laravel's sessions get a separate table. |
| `LOG_CHANNEL` | `stderr` | No persistent log files. |
| `TRUSTED_PROXIES` | `*` | Render terminates TLS; yap must see the public `https://` URL to build callbacks and validate signatures. |
| `TITLE`, `BMLT_ROOT_SERVER` | demo defaults | Edit in the dashboard. |

`APP_URL` defaults to Render's `RENDER_EXTERNAL_URL`, so the absolute URLs yap puts in TwiML point at the Render host. Set `APP_URL` yourself if you add a custom domain.

### Other yap settings

`config.php` is generated on boot, so any other [setting](../general/configuration-precedence) can be set with a `YAP_SETTING_<NAME>` env var, e.g. `YAP_SETTING_LANGUAGE=es-US` or `YAP_SETTING_SMS_ASK=true`. Values that parse as JSON (`true`, `30`, `["a","b"]`, `{"1":"x"}`) are decoded; anything else is used as a string.

## 3. Point the simulator at it

Run the simulator from a local checkout, signing with the same auth token you gave Render:

```bash
cd src
php artisan yap:simulate --base-url=https://<service>.onrender.com --auth-token=<TWILIO_AUTH_TOKEN> -v
```

Hit `/ping` first (or allow for the first webhook to be slow) if the service has been asleep.

`--seed` writes the test volunteer directly to the database the **local** checkout is configured for. To use it against the Render instance, point your local `config.php` at the same external database, or set the service body's volunteers up in the Render instance's admin UI and pass `--service-body` without `--seed`.

## Building the image yourself

```bash
docker build -f docker/Dockerfile.render -t yap-render .
docker run --rm -p 10000:10000 \
  -e APP_KEY=base64:$(openssl rand -base64 32) -e APP_ENV=demo \
  -e MYSQL_HOSTNAME=... -e MYSQL_USERNAME=... -e MYSQL_PASSWORD=... -e MYSQL_DATABASE=... \
  -e TITLE="Yap Demo" -e BMLT_ROOT_SERVER=https://latest.aws.bmlt.app/main_server \
  -e TWILIO_ACCOUNT_SID=... -e TWILIO_AUTH_TOKEN=... \
  -e SESSION_DRIVER=database -e SESSION_TABLE=laravel_sessions -e LOG_CHANNEL=stderr \
  yap-render
```

Set `MYSQL_TLS=false` when the database doesn't speak TLS (e.g. a local MySQL container).
