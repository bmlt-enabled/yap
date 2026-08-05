<?php
static $title = "Test Helpline";
# The PHPUnit/Pest suite never contacts this host: tests/Pest.php calls
# Http::preventStrayRequests() and tests/FakeHttp.php serves every BMLT call
# from tests/Fixtures/http/bmlt/. The URL is kept real because the fixtures were
# recorded from it, and because the Playwright e2e suite loads this same file
# (playwright.config.js runs its webserver with ENVIRONMENT=test) without any
# HTTP faking.
static $bmlt_root_server = "https://latest.aws.bmlt.app/main_server";
static $mysql_hostname = "127.0.0.1";
static $mysql_username = "root";
static $mysql_password = "yap_root_password";
static $mysql_database = "yap_test";
static $mysql_port = 3106;
#https://www.twilio.com/docs/iam/test-credentials
static $twilio_account_sid = "AC222a79bf52fdc8c3cf463b2846582b83";
static $twilio_auth_token = "92433fddb38394db9bb63c5cee66b5d9";
# Placeholder only - never sent anywhere. google_maps_api_key is one of
# SettingsService::$requiredSettings, so the suite needs *a* value, and the
# geocoder itself is served from tests/Fixtures/http/google/. This is what lets
# CI drop the GOOGLE_MAPS_API_KEY secret. A real key in the environment still
# wins, since SettingsService::get() prefers env("GOOGLE_MAPS_API_KEY").
static $google_maps_api_key = "not-a-real-key";
static $override_en_US_city_or_county = "city or suburb";
