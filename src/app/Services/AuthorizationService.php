<?php

namespace App\Services;

use App\Constants\AdminInterfaceRights;
use App\Models\RecordEvent;

class AuthorizationService
{
    private mixed $serviceBodyRights;

    public function __construct()
    {
        $this->serviceBodyRights = session()->get("auth_service_bodies_rights") ?? null;
    }

    public function getServiceBodyRights()
    {
        return $this->serviceBodyRights;
    }

    public function callsid($callsid, $event_id): bool
    {
        $recordEvent = RecordEvent::where('callsid', $callsid)->where('event_id', $event_id)->first();
        $serviceBodyId = $recordEvent->service_body_id;
        return in_array($serviceBodyId, $this->serviceBodyRights);
    }

    public function canManageUsers(): bool
    {
        // Check session first (for session-based auth)
        if (session()->has('auth_is_admin') && session()->get('auth_is_admin')) {
            return true;
        }
        if (session()->has('auth_permissions') && (intval(session()->get('auth_permissions')) & AdminInterfaceRights::MANAGE_USERS)) {
            return true;
        }

        // Fall back to checking authenticated user directly (for token-based auth)
        $user = auth()->user();
        if ($user) {
            if ($user->is_admin) {
                return true;
            }
            if ($user->permissions && (intval($user->permissions) & AdminInterfaceRights::MANAGE_USERS)) {
                return true;
            }
        }

        return false;
    }

    public function isTopLevelAdmin(): bool
    {
        // Check session first (for session-based auth)
        if (session()->has('auth_is_admin') && session()->get('auth_is_admin')) {
            return true;
        }

        // Fall back to checking authenticated user directly (for token-based auth)
        $user = auth()->user();
        return $user && $user->is_admin;
    }
}
