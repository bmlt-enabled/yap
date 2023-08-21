<?php

namespace App\Services;

abstract class Service
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings) {
        $this->settings = $settings;
    }

    public function settings(): SettingsService
    {
        return $this->settings;
    }
}
