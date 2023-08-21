<?php
namespace App\Services;

use App\Constants\EventId;
use App\Models\MetricsCollection;
use App\Models\RecordType;
use App\Repositories\ReportsRepository;
use Illuminate\Support\Facades\App;
use function App\Http\Controllers\Api\V1\Admin\findMetric;

class ReportsService extends Service
{
    private RootServerService $rootServerService;
    private ReportsRepository $reportsRepository;

    public function __construct(
        RootServerService $rootServerService,
        ReportsRepository $reportsRepository,
    ) {
        parent::__construct(App::make(SettingsService::class));
        $this->rootServerService = $rootServerService;
        $this->reportsRepository = $reportsRepository;
    }

    private function getServiceBodies($serviceBodyId, $recurse): array
    {
        if (intval($serviceBodyId) == 0) {
            return array_column($this->rootServerService->getServiceBodiesForUser(true), "id");
        } elseif ($recurse) {
            return $this->rootServerService->getServiceBodiesForUserRecursively($serviceBodyId);
        } else {
            return [$serviceBodyId];
        }
    }

    private function uniqueStdClassArray($array): array
    {
        $array = array_map('json_encode', $array);
        $array = array_unique($array);
        return array_map('json_decode', array_values($array));
    }

    private function findMetric($metrics, $date, $type)
    {
        foreach ($metrics as $metric) {
            if ($metric->timestamp == $date && ($metric->data == '{"searchType":"'.$type.'"}'
                    || $metric->data == '{"searchType":'.$type.'}')) {
                return $metric;
            }
        }

        return null;
    }

    public function getMetrics($serviceBodyId, $date_range_start, $date_range_end, $recurse = false) : MetricsCollection
    {
        $metricsCollection = new MetricsCollection();

        $reportsServiceBodies = $this->getServiceBodies($serviceBodyId, $recurse);
        $metricsCollection->metrics = $this->reportsRepository->getMetric(
            $reportsServiceBodies,
            $date_range_start,
            $date_range_end
        );
        $metricsCollection->summary = $this->reportsRepository->getMetricCounts(
            $reportsServiceBodies,
            $date_range_start,
            $date_range_end
        );
        $metricsCollection->calls = $this->reportsRepository->getAnsweredAndMissedCallMetrics(
            $reportsServiceBodies,
            $date_range_start,
            $date_range_end
        );
        $metricsCollection->volunteers = $this->reportsRepository->getAnsweredAndMissedVolunteerMetrics(
            $reportsServiceBodies,
            $date_range_start,
            $date_range_end
        );

        $all_metrics = array();
        if (count($metricsCollection->metrics) > 0) {
            $start_date = $metricsCollection->metrics[0]->timestamp;
            $end_date = $metricsCollection->metrics[count($metricsCollection->metrics) - 1]->timestamp;
            $current_date = $start_date;
            $metrics_types = array(EventId::VOLUNTEER_SEARCH, EventId::MEETING_SEARCH, EventId::JFT_LOOKUP,
                EventId::MEETING_SEARCH_SMS, EventId::VOLUNTEER_SEARCH_SMS, EventId::JFT_LOOKUP_SMS);
            while ($current_date <= $end_date) {
                foreach ($metrics_types as $metric_type) {
                    $fm = $this->findMetric($metricsCollection->metrics, $current_date, $metric_type);
                    if ($fm != null) {
                        array_push($all_metrics, $fm);
                    } else {
                        array_push($all_metrics, ['timestamp' => $current_date,
                            'counts' => 0,
                            'data' => sprintf('{"searchType":"%s"}', $metric_type)]);
                    }
                }

                $current_date = date('Y-m-d', strtotime($current_date . ' + 1 days'));
            }
        }

        $metricsCollection->metrics = $all_metrics;

        return $metricsCollection;
    }

    public function getMapMetricsCsv($serviceBodyId, $eventId, $date_range_start, $date_range_end, $recurse = false): string
    {
        $data = "lat,lon,name,desc\n";
        $metrics = $this->reportsRepository->getMapMetricByType(
            $this->getServiceBodies($serviceBodyId, $recurse),
            $eventId,
            $date_range_start,
            $date_range_end
        );
        $event_id = intval($eventId);
        foreach ($metrics as $metric) {
            $coordinates = json_decode($metric->meta)->coordinates;
            if ($coordinates->location != null) {
                $data .= sprintf(
                    "%s,%s,\"%s\",\"%s\"\n",
                    $coordinates->latitude,
                    $coordinates->longitude,
                    $coordinates->location,
                    $event_id
                );
            }
        }

        return $data;
    }

    public function getMapMetrics($serviceBodyId, $date_range_start, $date_range_end, $recurse = false): array
    {
        $results = [];
        $metrics = $this->reportsRepository->getMapMetrics(
            $this->getServiceBodies($serviceBodyId, $recurse),
            $date_range_start,
            $date_range_end
        );
        foreach ($metrics as $metric) {
            $coordinates = json_decode($metric->meta)->coordinates;
            if ($coordinates->location != null) {
                $results[] = $metric;
            }
        }

        return $results;
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
                $this->settingsService->logDebug("callEvents issue");
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

    public function getMisconfiguredPhoneNumbersAlerts($alertId): array
    {
        return $this->reportsRepository->getMisconfiguredPhoneNumbersAlerts($alertId);
    }
}
