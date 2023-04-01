<?php

namespace App\Services;

use App\Constants\EventId;
use App\Models\RecordType;
use App\Repositories\ReportsRepository;

class ReportsService
{
    private $rootServerService;
    private $reportsRepository;

    public function __construct(RootServerService $rootServerService, ReportsRepository $reportsRepository)
    {
        $this->rootServerService = $rootServerService;
        $this->reportsRepository = $reportsRepository;
    }

    private function getServiceBodies($serviceBodyId, $recurse)
    {
        if (intval($serviceBodyId) == 0) {
            return array_column($this->rootServerService->getServiceBodiesForUser(true), "id");
        } elseif ($recurse) {
            return $this->rootServerService->getServiceBodiesForUserRecursively($serviceBodyId);
        } else {
            return [$serviceBodyId];
        }
    }

    private function uniqueStdClassArray($array)
    {
        $array = array_map('json_encode', $array);
        $array = array_unique($array);
        return array_map('json_decode', array_values($array));
    }

    public function getCallDetailRecords($serviceBodyId, $date_range_start, $date_range_end, $recurse = false): array
    {
        $service_body_ids = $this->getServiceBodies($serviceBodyId, $recurse);
        $callRecords = $this->reportsRepository->getCallRecords($service_body_ids, $date_range_start, $date_range_end);

        foreach ($callRecords as &$callRecord) {
            $callRecord->type_name = RecordType::getTypeById($callRecord->type);
            $callEvents = isset($callRecord->call_events)
                ? $this->uniqueStdClassArray(json_decode($callRecord->call_events)) : [];

            if (!isset($callEvents)) {
                log_debug("callEvents issue");
            }

            foreach ($callEvents as &$callEvent) {
                $callEvent->parent_callsid = $callRecord->callsid;
                $callEvent->event_name = EventId::getEventById($callEvent->event_id);
                $callEvent->meta = json_encode($callEvent->meta);
            }
            $callRecord->call_events = $callEvents;
        }

        $response['data'] = $callRecords;
        $response['last_page'] = 1;

        return $response;
    }
}
