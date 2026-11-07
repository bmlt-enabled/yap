<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A scripted end-to-end flow. run() drives the simulator and throws
 * SimulationFailed when the instance does not behave as expected.
 */
interface Scenario
{
    public function name(): string;

    public function description(): string;

    /**
     * Why this scenario cannot run with $options, or null when it can. When
     * yap:simulate runs every scenario, these are skipped with the reason
     * instead of failing; named explicitly, they run and fail loudly.
     */
    public function skipReason(ScenarioOptions $options): ?string;

    /**
     * @return list<string> what was verified, for the run summary
     * @throws SimulationFailed
     */
    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array;
}
