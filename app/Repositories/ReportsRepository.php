<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReportsRepository
{
    public function getMapMetrics($service_body_ids, $date_range_start, $date_range_end)
    {
        return DB::select(
            "select event_id, meta from records_events where event_time >= ?
                                            AND event_time <= ? and event_id in (1,14) and meta is not null
            and service_body_id in (?)",
            [$date_range_start, $date_range_end, implode(", ", $service_body_ids)]
        );
    }


    public function getMapMetricByType($service_body_ids, $eventId, $date_range_start, $date_range_end)
    {
        return DB::select(
            "select event_id, meta from records_events where event_time >= ?
                                            AND event_time <= ? and event_id = ? and meta is not null
                                            and IFNULL(service_body_id,0) in (?)",
            [$date_range_start, $date_range_end, $eventId, implode(", ", $service_body_ids)]
        );
    }

    // TODO: add show multiple service bodies options
    public function getCallRecords($service_body_ids, $date_range_start, $date_range_end)
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
}
