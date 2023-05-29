<?php

namespace App\Services;

use App\Constants\MeetingResultSort;
use App\Models\MeetingResults;
use Countable;
use DateTime;
use Exception;

class MeetingResultsService
{
    protected SettingsService $settings;
    protected RootServerService $rootServer;
    protected TimeZoneService $timeZone;
    protected ConfigService $config;

    public function __construct(
        SettingsService $settings,
        RootServerService $rootServer,
        TimeZoneService $timeZone,
        ConfigService $config
    ) {
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->timeZone = $timeZone;
        $this->config = $config;
    }

    public function getMeetings($latitude, $longitude, $results_count, $today = null, $tomorrow = null)
    {
        if ($latitude != null & $longitude != null) {
            $this->setTimeZoneForLatitudeAndLongitude($latitude, $longitude);
            $graced_date_time = (new DateTime())->modify(sprintf("-%s minutes", $this->settings->get('grace_minutes')));
            if ($today == null) {
                $today = $graced_date_time->format("w") + 1;
            }
            if ($tomorrow == null) {
                $tomorrow = $graced_date_time->modify("+24 hours")->format("w") + 1;
            }
        }

        $meeting_results = new MeetingResults();
        $meeting_results = $this->meetingSearch($meeting_results, $latitude, $longitude, $today);
        if (count($meeting_results->filteredList) < $results_count) {
            $meeting_results = $this->meetingSearch($meeting_results, $latitude, $longitude, $tomorrow);
        }

        if ($meeting_results->originalListCount > 0) {
            if ($today == null) {
                $this->setTimeZoneForLatitudeAndLongitude(
                    $meeting_results->filteredList[0]->latitude,
                    $meeting_results->filteredList[0]->longitude
                );

                $today = (new DateTime())->format("w") + 1;
            }

            $sort_day_start = $this->settings->get('meeting_result_sort') == MeetingResultSort::TODAY
                ? $today : $this->settings->get('meeting_result_sort');

            $days = array_column($meeting_results->filteredList, 'weekday_tinyint');
            $today_str = strval($sort_day_start);
            $meeting_results->filteredList = array_merge(
                array_splice($meeting_results->filteredList, array_search($today_str, $days)),
                array_splice($meeting_results->filteredList, 0)
            );
        }

        return $meeting_results;
    }

    public function meetingSearch($meeting_results, $latitude, $longitude, $day)
    {
        $search_results = $this->rootServer->searchForMeetings($latitude, $longitude, $day);
        if (is_array($search_results->meetings) || $search_results->meetings instanceof Countable) {
            $meeting_results->originalListCount += count($search_results->meetings);
        } else {
            return $meeting_results;
        }

        $filteredList = $meeting_results->filteredList;
        if ($search_results !== null) {
            for ($i = 0; $i < count($search_results->meetings); $i++) {
                // Hide meetings if they are TC and are not VM formats.
                if (!in_array("VM", explode(",", $search_results->meetings[$i]->formats))
                    && in_array("TC", explode(",", $search_results->meetings[$i]->formats))) {
                    continue;
                }

                if (strpos($this->settings->get('custom_query'), "{DAY}")) {
                    if (!$this->isItPastTime(
                        $search_results->meetings[$i]->weekday_tinyint,
                        $search_results->meetings[$i]->start_time
                    )) {
                        $filteredList[] = $search_results->meetings[$i];
                    }
                } else {
                    $filteredList[] = $search_results->meetings[$i];
                }

                $formats = explode(",", $search_results->meetings[$i]->formats);
                $search_results->meetings[$i]->format_details = [];
                foreach ($formats as $format) {
                    foreach ($search_results->formats as $search_result_format) {
                        if ($format === $search_result_format->key_string) {
                            $search_results->meetings[$i]->format_details[] = $search_result_format;
                        }
                    }
                }
            }
        } else {
            $meeting_results->originalListCount += 0;
        }

        $meeting_results->filteredList = $filteredList;
        return $meeting_results;
    }

    public function getServiceBodyCoverage($latitude, $longitude)
    {
        $search_results = $this->rootServer->helplineSearch($latitude, $longitude);
        $service_bodies = $this->rootServer->getServiceBodiesForRouting($latitude, $longitude);
        $already_checked = [];

        // Must do this because the BMLT returns an empty object instead of an empty array.
        if (!is_array($search_results)) {
            throw new Exception(('helpline_no_results_found_retry'));
        }

        for ($j = 0; $j < count($search_results); $j++) {
            $service_body_id = $search_results[$j]->service_body_bigint;
            if (in_array($service_body_id, $already_checked)) {
                continue;
            }
            for ($i = 0; $i < count($service_bodies); $i++) {
                if ($service_bodies[$i]->id == $service_body_id) {
                    if ((isset($service_bodies[$i]->helpline) && strlen($service_bodies[$i]->helpline) > 0)
                        || $this->config->getServiceBodyCallHandling($service_bodies[$i]->id)->volunteer_routing_enabled) {
                        return $service_bodies[$i];
                    } else {
                        $already_checked[] = $service_bodies[$i]->id;
                    }
                }
            }
        }
    }

    private function setTimeZoneForLatitudeAndLongitude($latitude, $longitude)
    {
        $time_zone_results = $this->timeZone->getTimeZoneForCoordinates($latitude, $longitude);
        date_default_timezone_set($time_zone_results->timeZoneId);
    }

    private function isItPastTime($meeting_day, $meeting_time)
    {
        $next_meeting_time = $this->getNextMeetingInstance($meeting_day, $meeting_time);
        $time_zone_time = new DateTime();
        return $next_meeting_time <= $time_zone_time;
    }

    private function getNextMeetingInstance($meeting_day, $meeting_time)
    {
        $mod_meeting_day = (new DateTime())
            ->modify(sprintf("-%s minutes", $this->settings->get('grace_minutes')))
            ->modify(SettingsService::$dateCalculationsMap[$meeting_day])->format("Y-m-d");
        $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $meeting_time))
            ->modify(sprintf("+%s minutes", $this->settings->get('grace_minutes')));
        return $mod_meeting_datetime;
    }
}
