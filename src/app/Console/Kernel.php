<?php

namespace App\Console;

use App\Console\Commands\PreflightCommand;
use App\Console\Commands\SimulateCommand;
use App\Console\Commands\UndeleteVoicemailsCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * Commands are listed explicitly rather than discovered with
     * $this->load(__DIR__.'/Commands'): discovery resolves the app namespace
     * from composer.json, which the release zip does not ship. Web requests
     * that reach Artisan boot this kernel, so a missing composer.json 500s the
     * whole site (#1641). .github/scripts/smoke-package.sh fails the build if
     * a command in Commands/ is missing here or discovery comes back.
     *
     * @var array
     */
    protected $commands = [
        PreflightCommand::class,
        SimulateCommand::class,
        UndeleteVoicemailsCommand::class,
    ];

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
//        require base_path('routes/console.php');
    }
}
