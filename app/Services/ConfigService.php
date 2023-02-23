<?php

namespace App\Services;

use App\Constants\DataType;
use App\Repositories\ConfigRepository;
use ServiceBodyCallHandling;

class ConfigService
{
    protected ConfigRepository $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    public function getCallHandling($serviceBodyId)
    {
        $helplineData = $this->config->getDbData($serviceBodyId, DataType::YAP_CALL_HANDLING_V2);
        // TODO: this line needs to be reworked after functions.php is blown up
        return count($helplineData) > 0 ? getServiceBodyCallHandlingData(json_decode(json_encode($helplineData[0]), true))
            : getServiceBodyCallHandlingData(null);
    }
}
