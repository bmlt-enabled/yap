<?php

namespace App\Services;

use App\Constants\AdminInterfaceRights;
use App\Constants\AuthMechanism;
use App\Models\RecordsEvents;

class AuthorizationService
{
    private mixed $serviceBodyRights;

    public function __construct()
    {
        @session_start();
        $this->serviceBodyRights = isset($_SESSION["auth_service_bodies_rights"]) ? $_SESSION["auth_service_bodies_rights"] : null;
    }

    public function getServiceBodyRights()
    {
        return $this->serviceBodyRights;
    }

    public function callsid($callsid, $event_id)
    {
        $recordEvent = RecordsEvents::where('callsid', $callsid)->where('event_id', $event_id)->first();
        $serviceBodyId = $recordEvent->service_body_id;
        return in_array($serviceBodyId, $this->serviceBodyRights);
    }

    public function canManageUsers()
    {
        return (isset($_SESSION['auth_is_admin']) && boolval($_SESSION['auth_is_admin'])) ||
            (isset($_SESSION['auth_permissions']) && (intval($_SESSION['auth_permissions']) & AdminInterfaceRights::MANAGE_USERS));
    }

    public function isTopLevelAdmin()
    {
        return (isset($_SESSION['auth_is_admin']) && boolval($_SESSION['auth_is_admin']));
    }
}
