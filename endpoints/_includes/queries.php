<?php
function getVoicemail($service_body_id)
{
    $db = new Database();
    $sql = sprintf("SELECT r.`callsid`,r.`from_number`,r.`to_number`,CONCAT(re.`event_time`, 'Z') as event_time,re.`meta` FROM records_events re
    LEFT OUTER JOIN records r ON re.callsid = r.callsid where event_id = %d and service_body_id = %s;", EventId::VOICEMAIL, $service_body_id);
    $db->query($sql);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function setFlag($flag, $setting)
{
    $db = new Database();
    $db->query("INSERT INTO `flags` (`flag_name`,`flag_setting`)
      VALUES ('$flag'," . intval($setting) . ")");
    $db->execute();
    $db->close();
}

function getFlag($flag)
{
    $db = new Database();
    $db->query("SELECT flag_setting FROM `flags` WHERE `flag_name`=:flag");
    $db->bind(':flag', $flag);
    $db->execute();
    $resultset = $db->resultset();
    $db->close();
    return isset($resultset[0]["flag_setting"]) ? $resultset[0]["flag_setting"] : -1;
}

function getMapMetrics($service_body_ids)
{
    $db = new Database();
    $sql = sprintf(
        "select event_id, meta from records_events where event_id in (1,14) and meta is not null %s",
        "and service_body_id in (" . implode(", ", $service_body_ids) . ")"
    );
    $db->query($sql);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getMapMetricByType($service_body_ids, $eventId)
{
    $db = new Database();
    $sql = sprintf(
        "select event_id, meta from records_events where `event_id` = :eventId and meta is not null %s",
        "and service_body_id in (" . implode(", ", $service_body_ids) . ")"
    );
    $db->query($sql);
    $db->bind(":eventId", $eventId);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getCallRecordsCount($service_body_ids, $size)
{
    $db = new Database();
    $sql = sprintf(
        "select count(distinct r.callsid) as page_count from records r
inner join records_events re on r.callsid = re.callsid
left outer join conference_participants cp on r.callsid = cp.callsid or cp.callsid IS NULL %s",
        "WHERE `service_body_id` in (" . implode(",", $service_body_ids) . ")"
    );
    $db->query($sql);
    $resultset = $db->resultset();
    $db->close();
    return intval(ceil(intval($resultset[0]['page_count']) / $size));
}

function quickExec($sql) {
    $db = new Database();
    $db->query($sql);
    $db->execute();
    $db->close();
}

// TODO: add show multiple service bodies options
function getCallRecords($service_body_ids, $page, $size)
{
    $guid = uniqid();
    quickExec("INSERT INTO `cache_records_conference_participants` SELECT DISTINCT r.callsid as parent_callsid,cp2.callsid,'".$guid."' as guid FROM records r LEFT OUTER JOIN conference_participants cp ON r.callsid = cp.callsid OR cp.callsid IS NULL LEFT OUTER JOIN conference_participants cp2 ON cp.conferencesid = cp2.conferencesid;");
    quickExec("INSERT INTO `cache_records_records_conference_participants` SELECT r.id,r.callsid,r.start_time,r.end_time,r.from_number,r.to_number,r.duration,rcp.parent_callsid,'".$guid."' as guid FROM records r INNER JOIN cache_records_conference_participants rcp ON rcp.parent_callsid = r.callsid;");
    quickExec("DELETE FROM `cache_records_conference_participants` WHERE guid = '".$guid."'");

    $db = new Database();
    $sql = sprintf(
        "SELECT r.`id`,CONCAT(r.`start_time`, 'Z') as start_time,CONCAT(r.`end_time`, 'Z') as end_time,r.`duration`,r.`from_number`,r.`to_number`,r.`callsid`,
CONCAT('[', GROUP_CONCAT('{\"meta\":', IFNULL(re.meta, '{}'), ',\"event_id\":', re.event_id, ',\"event_time\":\"', re.event_time, 'Z\",\"service_body_id\":', COALESCE(re.service_body_id, 0), '}' ORDER BY re.event_time DESC SEPARATOR ','), ']') as call_events
FROM cache_records_records_conference_participants r
LEFT OUTER JOIN records_events re ON r.callsid = re.callsid
WHERE re.id IS NOT NULL %s
GROUP BY r.callsid
ORDER BY r.`id` DESC,CONCAT(r.`start_time`, 'Z') DESC %s",
        " AND `service_body_id` in (" . implode(",", $service_body_ids) . ")",
        " LIMIT " . $size . " OFFSET " .  ($page - 1) * $size
    );
    $db->exec("SET @@session.sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    $db->exec("SET @@session.group_concat_max_len = 4294967295;");
    $db->query($sql);
    $resultset = $db->resultset();
    $db->close();
    
    quickExec("DELETE FROM `cache_records_records_conference_participants` WHERE guid = '".$guid."'");
    
    return $resultset;
}

function insertCallRecord($callRecord)
{
    $db = new Database();
    $stmt = "INSERT INTO `records` (`callsid`,`from_number`,`to_number`,`duration`,`start_time`,`end_time`,`payload`)
        VALUES (:callSid, :from_number, :to_number, :duration, :start_time, :end_time, :payload)";
    $db->query($stmt);
    $db->bind(':callSid', $callRecord->callSid);
    $db->bind(':from_number', $callRecord->from_number);
    $db->bind(':to_number', $callRecord->to_number);
    $db->bind(':duration', $callRecord->duration);
    date_default_timezone_set('UTC');
    $db->bind(':start_time', $callRecord->start_time);
    $db->bind(':end_time', $callRecord->end_time);
    $db->bind(':payload', $callRecord->payload);
    $db->execute();
    $db->close();
}

function getMisconfiguredPhoneNumbersAlerts($alert_id)
{
    $db = new Database();
    $sql = sprintf("SELECT a.payload FROM alerts a
INNER JOIN (select to_number, max(start_time) as start_time FROM records GROUP BY to_number) b
ON a.payload = b.to_number and a.timestamp > b.start_time
where alert_id = %s
group by a.payload
UNION
SELECT a.payload FROM alerts a
LEFT OUTER JOIN records b ON a.payload = b.to_number
where alert_id = %s and b.to_number IS NULL", $alert_id, $alert_id);
    $db->query($sql);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function insertCallEventRecord($eventid, $meta = null)
{
    if (isset($_REQUEST['CallSid'])) {
        $callSid = $_REQUEST['CallSid'];
    } else {
        return;
    }
    if (in_array($eventid, [SearchType::VOLUNTEERS, SearchType::MEETINGS, SearchType::JFT])) {
        writeMetric(["searchType" => $eventid], setting('service_body_id'));
    }

    $meta_as_json = isset($meta) ? json_encode($meta) : null;

    $service_body_id = setting('service_body_id');

    $db = new Database();
    $stmt = "INSERT INTO `records_events` (`callsid`,`event_id`,`event_time`,`service_body_id`,`meta`) VALUES (:callSid, :eventid, :event_time, :service_body_id, :meta)";
    $db->query($stmt);
    $db->bind(':callSid', $callSid);
    $db->bind(':eventid', $eventid);
    date_default_timezone_set('UTC');
    $db->bind(':event_time', getCurrentTime());
    $db->bind(':service_body_id', $service_body_id);
    $db->bind(':meta', $meta_as_json);
    $db->execute();
    $db->close();
}

function writeMetric($data, $service_body_id = 0)
{
    $db = new Database();
    if ($service_body_id > 0) {
        $db->query("INSERT INTO `metrics` (`data`, `service_body_id`) VALUES (:data, :service_body_id)");
        $db->bind(':service_body_id', $service_body_id);
    } else {
        $db->query("INSERT INTO `metrics` (`data`) VALUES (:data)");
    }
    $db->bind(':data', json_encode($data));
    $db->execute();
    $db->close();
}

function getMetric($service_body_ids, $general)
{
    $db = new Database();
    $query = "SELECT DATE_FORMAT(timestamp, \"%Y-%m-%d\") as `timestamp`,
                                        COUNT(DATE_FORMAT(`timestamp`, \"%Y-%m-%d\")) as counts,
                                        `data`
                                        FROM `metrics`" . sprintf(
        "%s %s %s",
        "WHERE service_body_id in (:service_body_ids)",
        $general ? "OR service_body_id is NULL" : "",
        " GROUP BY DATE_FORMAT(`timestamp`, \"%Y-%m-%d\"), `data`"
    );
    $db->query($query);
    $db->bind(':service_body_ids', implode(",", $service_body_ids));
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getConferences($service_body_id)
{
    $db = new Database();
    $db->query("SELECT * FROM `conference_participants` WHERE `friendlyname` LIKE ':service_body_id_%';");
    $db->bind(':service_body_id', strval(intval($service_body_id)));
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function setConferenceParticipant($friendlyname, $role)
{
    require_once 'twilio-client.php';
    $conferences = $GLOBALS['twilioClient']->conferences->read(array ("friendlyName" => $friendlyname ));
    $conferencesid = $conferences[0]->sid;
    $callsid = $_REQUEST['CallSid'];
    $db = new Database();
    $stmt = "INSERT INTO `conference_participants` (`conferencesid`,`callsid`,`friendlyname`,`role`) VALUES (:conferencesid,:callsid,:friendlyname,:role)";
    $db->query($stmt);
    $db->bind(':conferencesid', $conferencesid);
    $db->bind(':callsid', $callsid);
    $db->bind(':friendlyname', $friendlyname);
    $db->bind(':role', $role);
    $db->execute();
    $db->close();
}

function getConferenceParticipant($callsid)
{
    $db = new Database();
    $db->query("SELECT DISTINCT callsid, role FROM conference_participants WHERE callsid = :callsid");
    $db->bind(':callsid', $callsid);
    $resultset = $db->single();
    $db->close();
    return $resultset;
}

function insertAlert($alertId, $payload)
{
    $db = new Database();
    $stmt = "INSERT INTO `alerts` (`timestamp`,`alert_id`,`payload`) VALUES (:timestamp, :alertId, :payload)";
    $db->query($stmt);
    $db->bind(':alertId', $alertId);
    date_default_timezone_set('UTC');
    $db->bind(':timestamp', getCurrentTime());
    $db->bind(':payload', $payload);
    $db->execute();
    $db->close();
}

function admin_PersistDbConfig($service_body_id, $data, $data_type, $parent_id = 0)
{
    $db = new Database();
    $current_data_check = isset($parent_id) && $parent_id > 0 ? getDbDataByParentId($parent_id, $data_type) : getDbData($service_body_id, $data_type);

    if (count($current_data_check) == 0 || $data_type == DataType::YAP_GROUPS_V2) {
        $parent_id = $parent_id == 0 ? null : $parent_id;
        $stmt = "INSERT INTO `config` (`service_body_id`,`data`,`data_type`,`parent_id`) VALUES (:service_body_id,:data,:data_type,:parent_id)";
        $db->query($stmt);
        $db->bind(':service_body_id', $service_body_id);
        $db->bind(':data', $data);
        $db->bind(':data_type', $data_type);
        $db->bind(':parent_id', $parent_id, PDO::PARAM_INT);
        $db->execute();
        $db->query("SELECT MAX(id) as id FROM `config` WHERE `service_body_id`=:service_body_id AND `data_type`=:data_type");
        $db->bind(':service_body_id', $service_body_id);
        $db->bind(':data_type', $data_type);
        $resultset = $db->resultset();
        $db->close();
        return $resultset[0]['id'];
    } else {
        $stmt = "UPDATE `config` SET `data`=:data WHERE `service_body_id`=:service_body_id AND `data_type`=:data_type";
        if (isset($parent_id) && $parent_id > 0) {
            $stmt .= " AND `parent_id`=:parent_id";
        }
        $db->query($stmt);
        $db->bind(':data', $data);
        $db->bind(':service_body_id', $service_body_id);
        $db->bind(':data_type', $data_type);
        if (isset($parent_id) && $parent_id > 0) {
            $db->bind(':parent_id', $parent_id);
        }
        $db->execute();
    }
}

function admin_PersistDbConfigById($id, $data)
{
    $db = new Database();
    $stmt = "UPDATE `config` SET `data`=:data WHERE `id`=:id";
    $db->query($stmt);
    $db->bind(':data', $data);
    $db->bind(':id', $id);
    $db->execute();
    $db->close();
}

function setDatabaseCacheValue($key, $value)
{
    $db = new Database();
    $stmt = "INSERT INTO `cache` (`key`, `value`) VALUES (:key, :value)";
    $db->query($stmt);
    $db->bind(':key', $key);
    $db->bind(':value', $value);
    $db->execute();
    $db->close();
}

function getDatabaseCacheValue($key)
{
    $db = new Database();
    $stmt = "SELECT `value` FROM `cache` WHERE `key`=:key ORDER BY id DESC";
    $db->query($stmt);
    $db->bind(':key', $key);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getDbData($service_body_id, $data_type)
{
    $db = new Database();
    $db->query("SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `service_body_id`=:service_body_id AND `data_type`=:data_type");
    $db->bind(':service_body_id', $service_body_id);
    $db->bind(':data_type', $data_type);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getDbDataById($id, $data_type)
{
    $db = new Database();
    $db->query("SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `id`=:id AND `data_type`=:data_type");
    $db->bind(':id', $id);
    $db->bind(':data_type', $data_type);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getDbDataByParentId($parent_id, $data_type)
{
    $db = new Database();
    $db->query("SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `parent_id`=:parent_id AND `data_type`=:data_type");
    $db->bind(':parent_id', $parent_id);
    $db->bind(':data_type', $data_type);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getAllDbData($data_type)
{
    $db = new Database();
    $db->query("SELECT `id`,`data`,`service_body_id`,`parent_id` FROM `config` WHERE `data_type`=:data_type");
    $db->bind(':data_type', $data_type);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getDbGroupsForServiceBody($service_body_id)
{
    $db = new Database();
    $db->query("SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `service_body_id`=:service_body_id AND `data_type`=:data_type");
    $db->bind(':service_body_id', $service_body_id);
    $db->bind(':data_type', DataType::YAP_GROUPS_V2);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function getUsers($service_bodies = null)
{
    $db = new Database();
    if ($service_bodies === null) {
        $db->query("SELECT id, name, username, is_admin, permissions, service_bodies, created_on FROM `users`");
    } else {
        if (count($service_bodies) > 0) {
            $query = "SELECT id, name, username, permissions, service_bodies, created_on FROM `users` WHERE";
            $service_bodies_query = "";
            foreach ($service_bodies as $service_body) {
                if ($service_bodies_query !== "") {
                    $service_bodies_query .= " OR";
                }
                $service_bodies_query .= " FIND_IN_SET('$service_body', `service_bodies`)";
            }
            $query .= "(" . $service_bodies_query . ") AND id <> " . $_SESSION['auth_id'];
            $db->query($query);
        }
    }

    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function deleteUser($id)
{
    $db = new Database();
    $stmt = "DELETE FROM `users` WHERE `id`=:id";
    $db->query($stmt);
    $db->bind(':id', $id);
    $db->execute();
    $db->close();
}

function editUser($data, $role)
{
    $stmt = "UPDATE `users` SET `name` = :name";
    if (strlen($data->password) > 0) {
        $stmt = $stmt . ", `password` = SHA2(:password, 256)";
    }

    if ($role === 'admin') {
        $stmt = $stmt . ", `username` = :username, `service_bodies` = :service_bodies";
    }

    $stmt = $stmt . ", `permissions` = :permissions where `id` = :id";
    $db = new Database();
    $db->query($stmt);
    $db->bind(':id', $data->id);
    $db->bind(':name', $data->name);
    $db->bind(':permissions', array_sum($data->permissions));

    if (strlen($data->password) > 0) {
        $db->bind(':password', $data->password);
    }

    if ($role === 'admin') {
        $db->bind(':username', $data->username);
        $db->bind(':service_bodies', implode(",", $data->service_bodies));
    }

    $db->execute();
    $db->close();
}

function saveUser($data)
{
    $db = new Database();
    $stmt = "INSERT INTO `users` (`name`, `username`, `password`, `permissions`, `service_bodies`, `is_admin`) VALUES (:name, :username, SHA2(:password, 256), :permissions, :service_bodies, 0)";
    $db->query($stmt);
    $db->bind(':name', $data->name);
    $db->bind(':username', $data->username);
    $db->bind(':password', $data->password);
    $db->bind(":permissions", array_sum($data->permissions));
    $db->bind(':service_bodies', implode(",", $data->service_bodies));
    $db->execute();
    $db->close();
}

function auth_v2($username, $password)
{
    $db = new Database();
    $db->query("SELECT id, name, username, password, is_admin, permissions, service_bodies FROM `users` WHERE `username` = :username AND `password` = SHA2(:password, 256)");
    $db->bind(':username', $username);
    $db->bind(':password', $password);
    $db->execute();
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}
