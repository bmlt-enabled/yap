#!/usr/bin/env bash
# Boot the console kernel from an extracted release package.
#
# The release zip excludes composer.json, artisan, etc. (see src/.gitattributes),
# and every web request boots the console kernel via the DatabaseMigrations
# middleware. Anything that only works with dev files present 500s every page
# on a real install (#1641), so check the package the way it actually ships.
set -euo pipefail

PACKAGE_DIR="${1:?usage: smoke-package.sh <extracted-package-dir>}"
cd "$PACKAGE_DIR"

php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
if (!array_key_exists("yap:voicemail-undelete", $kernel->all())) {
    fwrite(STDERR, "yap:voicemail-undelete is not registered\n");
    exit(1);
}
echo "Package console kernel boots OK\n";
'
