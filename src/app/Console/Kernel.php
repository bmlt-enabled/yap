<?php

namespace App\Console;

use App\Console\Commands\UndeleteVoicemailsCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * Commands are listed explicitly rather than discovered with
     * $this->load(__DIR__.'/Commands'): discovery resolves the app namespace
     * from composer.json, which the release zip does not ship. Every web
     * request boots this kernel (the DatabaseMigrations middleware calls
     * Artisan), so a missing composer.json 500s the whole site.
     *
     * @var array
     */
    protected $commands = [
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
