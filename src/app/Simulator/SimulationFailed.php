<?php

namespace App\Simulator;

use RuntimeException;

/**
 * A scenario expectation that did not hold, or a response the simulator could
 * not make sense of.
 */
class SimulationFailed extends RuntimeException
{
}
