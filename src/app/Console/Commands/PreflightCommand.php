<?php

namespace App\Console\Commands;

use App\Services\Preflight\PreflightService;
use Illuminate\Console\Command;

class PreflightCommand extends Command
{
    protected $signature = 'yap:preflight';

    protected $description = 'Validate environment and database readiness before upgrading to Yap 5.0';

    public function handle(PreflightService $preflight): int
    {
        $result = $preflight->run();

        $this->info('Yap preflight checks');
        $this->newLine();

        foreach ($result['checks'] as $check) {
            $status = $check['passed']
                ? 'PASS'
                : ($check['blocking'] ? 'FAIL' : 'WARN');

            $this->line(sprintf('[%s] %s', $status, $check['label']));
            $this->line('  ' . $check['message']);

            if (!$check['passed'] && !empty($check['remediation'])) {
                $this->line('  → ' . $check['remediation']);
            }

            $this->newLine();
        }

        if ($result['passed']) {
            $this->info('All blocking preflight checks passed.');

            return self::SUCCESS;
        }

        $this->error('One or more blocking preflight checks failed. Resolve them before upgrading.');

        return self::FAILURE;
    }
}
