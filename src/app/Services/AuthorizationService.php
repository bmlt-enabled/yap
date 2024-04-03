<?php

namespace App\Services;

use App\Constants\AdminInterfaceRights;
use App\Models\RecordsEvents;

class AuthorizationService
{
    private mixed $serviceBodyRights;

    public function __construct()
    {
        @session_start();
        $this->serviceBodyRights = $_SESSION["auth_service_bodies_rights"] ?? null;
    }

    public function getServiceBodyRights()
    {
        return $this->serviceBodyRights;
    }

    public function callsid($callsid, $event_id): bool
    {
        $recordEvent = RecordsEvents::where('callsid', $callsid)->where('event_id', $event_id)->first();
        $serviceBodyId = $recordEvent->service_body_id;
        return in_array($serviceBodyId, $this->serviceBodyRights);
    }

    public function canManageUsers(): bool
    {
        return (isset($_SESSION['auth_is_admin']) && $_SESSION['auth_is_admin']) ||
            (isset($_SESSION['auth_permissions']) && (intval($_SESSION['auth_permissions']) & AdminInterfaceRights::MANAGE_USERS));
    }

    public function isTopLevelAdmin(): bool
    {
        return (isset($_SESSION['auth_is_admin']) && $_SESSION['auth_is_admin']);
    }
}
