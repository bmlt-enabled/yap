<?php

namespace App\Repositories;

use App\Models\RecordType;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;

class ReportsRepository
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function getMetric($service_body_ids, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "select DATE_FORMAT(a.event_time, \"%Y-%m-%d\") as timestamp,
       COUNT(DATE_FORMAT(a.event_time, \"%Y-%m-%d\")) as counts,
       CONCAT('{\"searchType\":\"',a.event_id,'\"}') as data, IFNULL(b.service_body_id,0) as service_body_id
        from records_events a
        INNER JOIN (select callsid, IFNULL(service_body_id,0) as service_body_id from records_events
            where event_time >= ? AND event_time <= ?
            group by callsid, service_body_id) b on a.callsid = b.callsid
            WHERE a.event_id in (1,2,3,19,20,21) and IFNULL(b.service_body_id,0) in (?)
            GROUP BY DATE_FORMAT(a.event_time, \"%Y-%m-%d\"), a.event_id, b.service_body_id",
            [$date_range_start, $date_range_end, implode(",", $service_body_ids)]
        );
    }

    public function getMetricCounts($service_body_ids, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "select event_id, count(a.event_id) as counts from records_events a
            INNER JOIN (select callsid, IFNULL(service_body_id, 0) as service_body_id from records_events
            where event_time >= ? AND event_time <= ?
            group by callsid, service_body_id) b on a.callsid = b.callsid
            WHERE a.event_id in (1,2,3,12,19,20,21) and IFNULL(b.service_body_id,0) in (?)
            GROUP BY a.event_id ORDER BY a.event_id",
            [$date_range_start, $date_range_end, implode(",", $service_body_ids),]
        );
    }

    public function getAnsweredAndMissedCallMetrics($service_body_ids, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "SELECT
            a.service_body_id,
            b.conferencesid,
            sum(case when a.event_id = 12 then 1 else 0 end) as answered_count,
            sum(case when a.event_id = 7 or a.event_id = 8 then 1 else 0 end) as missed_count
            from records_events a
            INNER JOIN (select re.callsid, conferencesid, IFNULL(service_body_id,0) as service_body_id
                        from records_events re
			inner join conference_participants cp on re.callsid = cp.callsid
            where event_time >= ? AND event_time <= ?
            group by re.callsid, conferencesid, service_body_id) b on a.callsid = b.callsid
            WHERE a.event_id in (7, 8, 12) and IFNULL(b.service_body_id,0) in (?)
            GROUP BY a.service_body_id, b.conferencesid",
            [$date_range_start, $date_range_end, implode(",", $service_body_ids)]
        );
    }

    public function getAnsweredAndMissedVolunteerMetrics($service_body_ids, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "select a.meta,
            a.service_body_id,
            sum(case when a.event_id = 6 or a.event_id = 9 then 1 else 0 end) as answered_count,
            sum(case when a.event_id = 8 then 1 else 0 end) as missed_count
            from records_events a
            INNER JOIN (select callsid, IFNULL(service_body_id,0) as service_body_id
                        from records_events
            where event_time >= ? AND event_time <= ?
            group by callsid, service_body_id) b on a.callsid = b.callsid
            WHERE a.event_id in (6, 8, 9) and IFNULL(b.service_body_id,0) in (?)
            GROUP BY a.meta, a.service_body_id",
            [$date_range_start, $date_range_end, implode(",", $service_body_ids)]
        );
    }

    public function getMapMetrics($service_body_ids, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "select event_id, meta from records_events where event_time >= ?
            AND event_time <= ? and event_id in (1,14) and meta is not null
            and service_body_id in (?)",
            [$date_range_start, $date_range_end, implode(", ", $service_body_ids)]
        );
    }


    public function getMapMetricByType($service_body_ids, $eventId, $date_range_start, $date_range_end): array
    {
        return DB::select(
            "select event_id, meta from records_events where event_time >= ?
                                            AND event_time <= ? and event_id = ? and meta is not null
                                            and IFNULL(service_body_id,0) in (?)",
            [$date_range_start, $date_range_end, $eventId, implode(", ", $service_body_ids)]
        );
    }

    // TODO: add show multiple service bodies options
    public function getCallRecords($service_body_ids, $date_range_start, $date_range_end): array
    {
        $guid = uniqid();

        DB::insert(
            "INSERT INTO `cache_records_conference_participants`
SELECT DISTINCT r.callsid as parent_callsid,cp2.callsid,? as guid,IFNULL(re.service_body_id,0) as service_body_id
FROM records r
LEFT OUTER JOIN conference_participants cp ON r.callsid = cp.callsid OR cp.callsid IS NULL
LEFT OUTER JOIN conference_participants cp2 ON cp.conferencesid = cp2.conferencesid
LEFT OUTER JOIN records_events re ON cp.callsid = re.callsid
WHERE r.start_time >= ? AND r.start_time <= ?
UNION
SELECT DISTINCT r.callsid as parent_callsid,r.callsid,? as guid,IFNULL(re.service_body_id,0) as service_body_id
FROM records r
LEFT OUTER JOIN records_events re on r.callsid = re.callsid
WHERE r.start_time >= ? AND r.start_time <= ?",
            [$guid, $date_range_start, $date_range_end, $guid, $date_range_start, $date_range_end,]
        );

        DB::statement(DB::raw("SET @@session.sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"));
        DB::statement(DB::raw("SET @@session.group_concat_max_len = 4294967295;"));
        $resultset = DB::select(sprintf("SELECT r.`id`,CONCAT(r.`start_time`, 'Z') as start_time,CONCAT(r.`end_time`, 'Z') as end_time,r.`duration`,r.`from_number`,r.`to_number`,r.`callsid`,re.`service_body_id`,IFNULL(r.`type`, 1) as `type`,
CONCAT('[', GROUP_CONCAT('{\"meta\":', IFNULL(re.meta, '{}'), ',\"event_id\":', re.event_id, ',\"event_time\":\"', re.event_time, 'Z\",\"service_body_id\":', COALESCE(re.service_body_id, 0), '}' ORDER BY re.event_time DESC SEPARATOR ','), ']') as call_events
FROM (SELECT ire.id,ire.callsid, ire.event_time,ire.event_id,ircp.service_body_id,meta FROM records_events ire
      left outer join cache_records_conference_participants ircp ON ire.callsid = ircp.callsid
      where guid = ?) re
INNER JOIN cache_records_conference_participants rcp ON rcp.callsid = re.callsid
INNER JOIN records r ON r.callsid = rcp.parent_callsid
WHERE re.service_body_id in (%s) AND rcp.guid = ?
GROUP BY rcp.parent_callsid
ORDER BY r.`id` DESC,CONCAT(r.`start_time`, 'Z') DESC", implode(",", $service_body_ids)), [$guid, $guid]);

        DB::delete("DELETE FROM cache_records_conference_participants WHERE guid = ?", [$guid]);

        return $resultset;
    }

    public function insertCallEventRecord($eventid, $meta = null)
    {
        if (isset($_REQUEST['CallSid'])) {
            $callSid = $_REQUEST['CallSid'];
            $type = RecordType::PHONE;
        } elseif (isset($_REQUEST['SmsSid'])) {
            $callSid = $_REQUEST['SmsSid'];
            $type = RecordType::SMS;
        } else {
            return;
        }

        $meta_as_json = isset($meta) ? json_encode($meta) : null;

        $service_body_id = $this->settings->get('service_body_id');
        date_default_timezone_set('UTC');

        DB::insert(
            "INSERT INTO `records_events` (`callsid`,`event_id`,`event_time`,`service_body_id`,`meta`, `type`)
            VALUES (?, ?, ?, ?, ?, ?)",
            [$callSid, $eventid,  getCurrentTime(), $service_body_id, $meta_as_json, $type]
        );
    }

    public function insertCallRecord($callRecord)
    {
        date_default_timezone_set('UTC');
        DB::insert(
            "INSERT INTO `records`
            (`callsid`,`from_number`,`to_number`,`duration`,`start_time`,`end_time`,`payload`,`type`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                    $callRecord->callSid,
                    $callRecord->from_number,
                    $callRecord->to_number,
                    $callRecord->duration,
                    $callRecord->start_time,
                    $callRecord->end_time,
                    $callRecord->payload,
                    $callRecord->type
                ]
        );
    }

    public function setConferenceParticipant($friendlyname, $conferencesid, $callsid, $role)
    {
        DB::insert(
            "INSERT INTO `conference_participants` (`conferencesid`,`callsid`,`friendlyname`,`role`)
                VALUES (?,?,?,?)",
            [$conferencesid, $callsid, $friendlyname, $role]
        );
    }

    public function isDialbackPinValid($pin)
    {
        return DB::select(
            "SELECT from_number FROM sessions a INNER JOIN records b ON
        a.callsid = b.callsid where pin = ? order by start_time desc limit 1",
            [$pin]
        );
    }
}
