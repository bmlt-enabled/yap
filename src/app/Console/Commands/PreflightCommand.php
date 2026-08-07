<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;

class PreflightCommand extends Command
{
    protected $signature = 'yap:preflight';

    protected $description = 'Pre-deployment checks for Yap 5.x (Twilio signature validation, proxy config, required settings)';

    public function handle(SettingsService $settings): int
    {
        $failed = false;

        if (empty($settings->get('twilio_auth_token'))) {
            $failed = true;
            $this->error(
                'twilio_auth_token is empty. Yap 5.0 validates Twilio signatures on every inbound webhook and fails closed: '
                . 'without an auth token, every call and SMS to the IVR returns HTTP 403. '
                . 'Set $twilio_auth_token in config.php to the Auth Token for the Twilio account that owns your phone numbers.'
            );
        }

        if (config('twilio.disable_signature_validation') && !app()->environment('production')) {
            $this->warn(
                'TWILIO_DISABLE_SIGNATURE_VALIDATION is enabled. Signature checks are bypassed outside production only; '
                . 'do not set this on a live helpline.'
            );
        }

        $trustedProxies = config('trustedproxy.proxies');
        if (empty($trustedProxies)) {
            $this->line(
                'TRUSTED_PROXIES is not set (default). Direct connections and tests need no proxy trust. '
                . 'If Yap sits behind ngrok, a load balancer, or another reverse proxy, set TRUSTED_PROXIES=* '
                . 'or a comma-separated list of proxy IPs so Twilio signature validation sees the public URL Twilio signed.'
            );
        }

        foreach ($settings->minimalRequiredSettings() as $setting) {
            if ($setting === 'twilio_auth_token') {
                continue;
            }

            if (!$settings->has($setting) || $settings->get($setting) === '') {
                $failed = true;
                $this->error("Missing required setting: {$setting}");
            }
        }

        if ($failed) {
            $this->error('Preflight failed. Resolve the issues above before upgrading or taking traffic.');

            return self::FAILURE;
        }

        $this->info('Preflight passed.');

        return self::SUCCESS;
    }
}
