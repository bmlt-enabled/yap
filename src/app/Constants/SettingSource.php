<?php

namespace App\Constants;

class SettingSource
{
    const QUERYSTRING = "Transaction Override";
    const SESSION = "Session Override";
    const ENVIRONMENT = "Environment Variable";
    const CONFIG = "config.php";
    const DEFAULT_SETTING = "Factory Default";
}
