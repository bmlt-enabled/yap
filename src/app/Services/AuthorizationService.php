<?php

namespace App\Services;

use App\Constants\AdminInterfaceRights;
use App\Models\RecordEvent;

/**
 * Admin API permission checks.
 *
 * Every answer comes from the authenticated (Sanctum token) user's row:
 * is_admin, permissions and service_bodies. The session is deliberately never
 * consulted, so a leftover or planted session cannot grant anything, and a
 * token means the same thing with or without a session cookie
 * (bmlt-enabled/yap#1674).
 */
class AuthorizationService
{
    private ?array $serviceBodyRights = null;
    private RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        $this->rootServerService = $rootServerService;
    }

    /**
     * Ids of the service bodies the user may administer.
     *
     * @return int[]|string[]
     */
    public function getServiceBodyRights(): array
    {
        if ($this->serviceBodyRights === null) {
            $this->serviceBodyRights = $this->rootServerService->getServiceBodiesRightsIds();
        }

        return $this->serviceBodyRights;
    }

    public function canAccessServiceBody($serviceBodyId): bool
    {
        return $this->isTopLevelAdmin() || in_array($serviceBodyId, $this->getServiceBodyRights());
    }

    public function callsid($callsid, $event_id): bool
    {
        $recordEvent = RecordEvent::where('callsid', $callsid)->where('event_id', $event_id)->first();
        $serviceBodyId = $recordEvent->service_body_id;
        return in_array($serviceBodyId, $this->getServiceBodyRights());
    }

    public function canManageUsers(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return (bool) $user->is_admin
            || (intval($user->permissions) & AdminInterfaceRights::MANAGE_USERS) !== 0;
    }

    public function isTopLevelAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->is_admin;
    }

    public function isRootServiceBodyAdmin(): bool
    {
        if ($this->isTopLevelAdmin()) {
            return true;
        }

        // Check if any of user's service bodies is a root (no parent)
        foreach ($this->getServiceBodyRights() as $serviceBodyId) {
            $serviceBody = $this->rootServerService->getServiceBody($serviceBodyId);
            if ($serviceBody && ($serviceBody->parent_id === null || $serviceBody->parent_id === 0 || $serviceBody->parent_id === "0")) {
                return true;
            }
        }
        return false;
    }
}
