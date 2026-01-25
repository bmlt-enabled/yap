<?php

namespace App\Services;

use App\Constants\AdminInterfaceRights;
use App\Models\RecordEvent;

class AuthorizationService
{
    private mixed $serviceBodyRights;
    private RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        $this->serviceBodyRights = session()->get("auth_service_bodies_rights") ?? null;
        $this->rootServerService = $rootServerService;
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

    public function isRootServiceBodyAdmin(): bool
    {
        if ($this->isTopLevelAdmin()) {
            return true;
        }

        $serviceBodyRights = $this->getServiceBodyRights();
        if (!$serviceBodyRights || empty($serviceBodyRights)) {
            return false;
        }

        // Check if any of user's service bodies is a root (no parent)
        foreach ($serviceBodyRights as $serviceBodyId) {
            $serviceBody = $this->rootServerService->getServiceBody($serviceBodyId);
            if ($serviceBody && ($serviceBody->parent_id === null || $serviceBody->parent_id === 0 || $serviceBody->parent_id === "0")) {
                return true;
            }
        }
        return false;
    }
}
