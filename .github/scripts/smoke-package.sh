#!/usr/bin/env bash
# Boot the app from a release package, the way it ships.
#
# The release zip excludes composer.json, artisan, etc. (see src/.gitattributes).
# Anything that only works with those dev files present breaks every install;
# on 4.5.3-beta1 console command discovery read composer.json and every page
# returned HTTP 500 (#1641). This checks the packaged app, not the checkout:
#
#   1. The console kernel boots, every class in app/Console/Commands is
#      registered, and no command directory is discovered via load().
#   2. Requests through the HTTP kernel do not return a 500: once as a fresh
#      install (no config.php, the installer page) and once with a minimal
#      config.php so the request reaches the router and a controller.
#
# Runs against a scratch copy, so the package itself is never modified.
#
# usage: smoke-package.sh <package.zip | extracted-package-dir>
set -euo pipefail

TARGET="${1:?usage: smoke-package.sh <package.zip | extracted-package-dir>}"

WORK_DIR="$(mktemp -d "${RUNNER_TEMP:-${TMPDIR:-/tmp}}/yap-smoke.XXXXXX")"
trap 'rm -rf "$WORK_DIR"' EXIT

if [[ -f "$TARGET" ]]; then
    unzip -q "$TARGET" -d "$WORK_DIR"
    PACKAGE_DIR="$(find "$WORK_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
elif [[ -d "$TARGET" ]]; then
    PACKAGE_DIR="$WORK_DIR/package"
    cp -R "$TARGET" "$PACKAGE_DIR"
else
    echo "smoke-package.sh: $TARGET is not a zip or a directory" >&2
    exit 2
fi

cd "$PACKAGE_DIR"

for dev_file in composer.json artisan; do
    if [[ -e "$dev_file" ]]; then
        echo "::warning::$dev_file is in the package; this smoke test no longer proves the app boots without it"
    fi
done
if [[ -e config.php ]]; then
    echo "smoke-package.sh: the package ships a config.php" >&2
    exit 1
fi

echo "== console kernel"
php -d display_errors=stderr -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$registered = $kernel->all();

$failed = false;

// Every command class shipped in app/Console/Commands must be registered.
foreach (glob("app/Console/Commands/*.php") as $file) {
    $class = "App\\Console\\Commands\\" . basename($file, ".php");
    $found = false;
    foreach ($registered as $command) {
        if ($command instanceof $class) {
            $found = true;
            echo "registered: {$command->getName()} ({$class})\n";
            break;
        }
    }
    if (!$found) {
        fwrite(STDERR, "{$class} is not registered; add it to App\\Console\\Kernel::\$commands\n");
        $failed = true;
    }
}

foreach (["yap:voicemail-undelete"] as $name) {
    if (!array_key_exists($name, $registered)) {
        fwrite(STDERR, "{$name} is not registered\n");
        $failed = true;
    }
}

// Discovery resolves the app namespace from composer.json, which the zip
// does not ship. List commands in Kernel::$commands instead (#1641). When
// bootstrap/app.php presets the namespace, discovery works without
// composer.json, so the boot above cannot catch it; check for it directly.
// (Laravel 10 has no $loadedPaths; there discovery throws during the boot.)
$loadedPaths = property_exists($kernel, "loadedPaths")
    ? (new ReflectionProperty($kernel, "loadedPaths"))->getValue($kernel)
    : [];
if (!empty($loadedPaths)) {
    fwrite(STDERR, "Console kernel discovers commands with load(): " . implode(", ", $loadedPaths)
        . "\nList them in App\\Console\\Kernel::\$commands instead; discovery needs composer.json (#1641)\n");
    $failed = true;
}

if ($failed) {
    exit(1);
}
echo "Package console kernel boots OK\n";
'

# Send one request through the HTTP kernel in a fresh PHP process; fail on 5xx.
# $2, when given, must appear in the response body.
http_probe() {
    local label="$1" expect="${2:-}"
    echo "== HTTP kernel: $label"
    EXPECT="$expect" php -d display_errors=stderr -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create("/ping", "GET");
// A web SAPI fills $_SERVER; some middleware reads it directly.
$_SERVER = array_merge($_SERVER, $request->server->all());
$response = $kernel->handle($request);
$status = $response->getStatusCode();
$body = (string) $response->getContent();
$kernel->terminate($request, $response);
$expect = getenv("EXPECT");
if ($status >= 500 || ($expect !== "" && !str_contains($body, $expect))) {
    fwrite(STDERR, "GET /ping returned HTTP {$status} from the packaged app"
        . ($expect !== "" ? " (expected body to contain \"{$expect}\")" : "") . "\n");
    fwrite(STDERR, substr($body, 0, 4000) . "\n");
    exit(1);
}
echo "GET /ping -> HTTP {$status}\n";
'
}

# Fresh install: no config.php, so ConfigCheck serves the installer page.
http_probe "fresh install (no config.php)"

# Configured install without a database: ConfigCheck and DatabaseMigrations
# pass through, so the request is routed to PingController.
cat > config.php <<'PHP'
<?php
static $title = "Yap package smoke test";
static $bmlt_root_server = "https://example.invalid/main_server";
static $google_maps_api_key = "smoke-test";
static $twilio_account_sid = "AC00000000000000000000000000000000";
static $twilio_auth_token = "smoke-test";
static $bmlt_username = "smoke-test";
static $bmlt_password = "smoke-test";
PHP
http_probe "configured, routed to /ping" "PONG"

echo "Package smoke test passed"
