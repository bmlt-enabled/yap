<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use App\Simulator\HttpTransport;
use App\Simulator\Scenarios\HelplineVolunteerScenario;
use App\Simulator\Scenarios\ScenarioOptions;
use App\Simulator\Scenarios\ScenarioRegistry;
use App\Simulator\SimulationFailed;
use App\Simulator\Transport;
use App\Simulator\TwilioSimulator;
use Illuminate\Console\Command;

/**
 * Drive scripted call and SMS flows against a running yap instance over real
 * HTTP, standing in for Twilio. The target must run with TWILIO_FAKE_MODE=true
 * so its outbound Twilio API calls are captured rather than sent.
 *
 * See docs/docs/miscellaneous/simulator.md.
 */
class SimulateCommand extends Command
{
    protected $signature = 'yap:simulate
        {scenarios?* : Scenarios to run (default: all of them)}
        {--list : List the available scenarios and exit}
        {--base-url= : Base URL of the yap instance (default: YAP_SIMULATOR_BASE_URL, then APP_URL)}
        {--account-sid= : Twilio account SID to send (default: twilio_account_sid from config.php)}
        {--auth-token= : Auth token used to sign requests (default: twilio_auth_token from config.php)}
        {--from=+17325551212 : Caller / texter number}
        {--to=+12125551212 : The helpline number being called or texted}
        {--location=14456 : Zip code the caller enters or texts}
        {--latitude=42.867970 : Latitude the location resolves to}
        {--longitude=-76.985573 : Longitude the location resolves to}
        {--timezone=America/New_York : Time zone the location is in}
        {--real-geocoding : Use Google Maps geocoding/time zone APIs instead of pinning --latitude/--longitude/--timezone}
        {--service-body= : Service body id for helpline-volunteer}
        {--volunteer= : Volunteer number helpline-volunteer expects to be dialed}
        {--outcome=answer : helpline-volunteer outcome: answer or no-answer}
        {--seed : Create volunteer routing for --service-body in this app\'s database first}
        {--no-reset : Keep previously captured fake Twilio requests}';

    protected $description = 'Simulate Twilio calls and SMS end to end against a yap instance running in fake Twilio mode';

    public function handle(SettingsService $settings): int
    {
        $registry = ScenarioRegistry::all();

        if ($this->option('list')) {
            foreach ($registry as $name => $scenario) {
                $this->line(sprintf('  %-20s %s', $name, $scenario->description()));
            }

            return self::SUCCESS;
        }

        $names = $this->argument('scenarios') ?: array_keys($registry);
        $unknown = array_diff($names, array_keys($registry));
        if ($unknown !== []) {
            $this->error('Unknown scenario(s): ' . implode(', ', $unknown) . '. Use --list to see them.');

            return self::INVALID;
        }

        if ($this->option('seed') && app()->environment('production')) {
            $this->error('--seed writes volunteer routing to the database and is refused in production.');

            return self::INVALID;
        }

        $options = new ScenarioOptions(
            from: (string) $this->option('from'),
            to: (string) $this->option('to'),
            location: (string) $this->option('location'),
            latitude: (string) $this->option('latitude'),
            longitude: (string) $this->option('longitude'),
            timezone: (string) $this->option('timezone'),
            realGeocoding: (bool) $this->option('real-geocoding'),
            serviceBodyId: $this->option('service-body'),
            volunteer: $this->option('volunteer'),
            outcome: (string) $this->option('outcome'),
            seed: (bool) $this->option('seed'),
        );

        $baseUrl = $this->option('base-url') ?: (env('YAP_SIMULATOR_BASE_URL') ?: config('app.url'));
        $simulator = new TwilioSimulator(
            $baseUrl,
            $this->option('account-sid') ?: (string) $settings->get('twilio_account_sid'),
            $this->option('auth-token') ?: (string) $settings->get('twilio_auth_token'),
            // The Pest suite binds a transport that dispatches in-process.
            app()->bound(Transport::class) ? app(Transport::class) : new HttpTransport(),
            fn (string $line) => $this->output->isVerbose() ? $this->line("    {$line}") : null,
        );

        $this->info("Simulating Twilio against {$baseUrl}");

        $failed = 0;
        try {
            if (!$this->option('no-reset')) {
                $simulator->reset();
            }
        } catch (SimulationFailed $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($names as $name) {
            $scenario = $registry[$name];

            if ($scenario instanceof HelplineVolunteerScenario && $options->serviceBodyId === null
                && $this->argument('scenarios') === []) {
                $this->line("<comment>SKIP</comment> {$name}: pass --service-body (and --seed locally) to run it");
                continue;
            }

            $this->line("<info>RUN </info> {$name}: {$scenario->description()}");
            try {
                foreach ($scenario->run($simulator, $options) as $check) {
                    $this->line("     ✓ {$check}");
                }
                $this->line("<info>PASS</info> {$name}");
            } catch (SimulationFailed $e) {
                $failed++;
                $this->line("<error>FAIL</error> {$name}");
                $this->line('     ' . str_replace("\n", "\n     ", $e->getMessage()));
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
