#!/usr/bin/bash
set -e

vendor/pestphp/pest/bin/pest --configuration phpunit.xml tests
