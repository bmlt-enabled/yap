<?php

namespace App\Repositories;

use App\Services\SettingsService;

abstract class Repository
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function settings(): SettingsService
    {
        return $this->settings;
    }
}
