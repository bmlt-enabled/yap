<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * Helpers the scenarios share. A scenario that configures the instance (call
 * handling, volunteers) does it with --seed, through ScenarioSeeder, so those
 * declare $needsSeed and, when they route by service body, $needsServiceBody.
 */
abstract class BaseScenario implements Scenario
{
    protected bool $needsSeed = false;

    protected bool $needsServiceBody = false;

    public function skipReason(ScenarioOptions $options): ?string
    {
        if ($this->needsServiceBody && $options->serviceBodyId === null) {
            return 'pass --service-body (and --seed locally) to run it';
        }
        if ($this->needsSeed && !$options->seed) {
            return 'pass --seed to run it (it configures the instance through this app\'s database)';
        }

        return null;
    }

    /** Fail unless the run can configure the instance the way this scenario needs. */
    protected function requireOptions(ScenarioOptions $options): void
    {
        $reason = $this->skipReason($options);
        if ($reason !== null) {
            throw new SimulationFailed("{$this->name()} needs more options: {$reason}.");
        }
    }

    protected function serviceBodyId(ScenarioOptions $options): int
    {
        return (int) $options->serviceBodyId;
    }

    /**
     * Start an inbound call with the scenario's location pinned and $query
     * riding on the first webhook (override_* settings for this call).
     *
     * @param array<string, mixed> $query
     */
    protected function call(TwilioSimulator $simulator, ScenarioOptions $options, array $query = [], ?string $from = null): SimulatedCall
    {
        return $simulator->startCall($from ?? $options->from, $options->to, array_merge($options->locationOverrides(), $query));
    }

    /**
     * Exactly one SMS to $to since $mark, or fail.
     *
     * @return array<string, mixed>
     */
    protected function onlyText(TwilioSimulator $simulator, int $mark, string $to, string $what): array
    {
        $texts = $simulator->texts($mark, $to);
        if (count($texts) !== 1) {
            throw new SimulationFailed(sprintf(
                "Expected one SMS to %s (%s), but %d were sent:\n  %s",
                $to,
                $what,
                count($texts),
                implode("\n  ", array_map(fn ($text) => $text['data']['Body'] ?? '', $texts)) ?: '(none)',
            ));
        }

        return $texts[0];
    }

    /** Assert $haystack contains $needle (case-insensitive). */
    protected static function expectContains(string $haystack, string $needle, string $what): void
    {
        if (stripos($haystack, $needle) === false) {
            throw new SimulationFailed("Expected {$what} to contain \"{$needle}\", got:\n  {$haystack}");
        }
    }
}
