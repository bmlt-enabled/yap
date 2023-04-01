<?php
function getVoicemail($service_body_id)
{
    $db = new Database();
    $sql = sprintf("SELECT r.`callsid`,s.`pin`,r.`from_number`,r.`to_number`,CONCAT(re.`event_time`, 'Z') as event_time,re.`meta` FROM records_events re
    LEFT OUTER JOIN records r ON re.callsid = r.callsid
    LEFT OUTER JOIN sessions s ON r.callsid = s.callsid
    LEFT OUTER JOIN event_status es ON re.callsid = es.callsid where re.event_id = %d and service_body_id = %s and (es.event_id = %s and es.status <> %s OR es.id IS NULL);", EventId::VOICEMAIL, $service_body_id, EventId::VOICEMAIL, EventStatusId::VOICEMAIL_DELETED);
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
    $resultset = $db->resultset();
    $db->close();
    return isset($resultset[0]["flag_setting"]) ? $resultset[0]["flag_setting"] : -1;
}

function insertCallRecord($callRecord)
{
    $db = new Database();
    $stmt = "INSERT INTO `records` (`callsid`,`from_number`,`to_number`,`duration`,`start_time`,`end_time`,`payload`,`type`)
        VALUES (:callSid, :from_number, :to_number, :duration, :start_time, :end_time, :payload, :type)";
    $db->query($stmt);
    $db->bind(':callSid', $callRecord->callSid);
    $db->bind(':from_number', $callRecord->from_number);
    $db->bind(':to_number', $callRecord->to_number);
    $db->bind(':duration', $callRecord->duration);
    date_default_timezone_set('UTC');
    $db->bind(':start_time', $callRecord->start_time);
    $db->bind(':end_time', $callRecord->end_time);
    $db->bind(':payload', $callRecord->payload);
    $db->bind(':type', $callRecord->type);
    $db->execute();
    $db->close();
}

function insertSession($callsid)
{
    $pin = lookupPinForCallSid($callsid);

    if (count($pin) == 0) {
        $db = new Database();
        $stmt = "INSERT INTO `sessions` (`callsid`,`pin`) VALUES (:callsid, :pin)";
        $db->query($stmt);
        $db->bind(':callsid', $callsid);
        $db->bind(':pin', rand(1000000, 9999999));
        $db->execute();
        $db->close();
    }
}

function isDialbackPinValid($pin)
{
    $db = new Database();
    $sql = sprintf("SELECT from_number FROM sessions a INNER JOIN records b ON a.callsid = b.callsid where pin = :pin order by start_time desc limit 1");
    $db->query($sql);
    $db->bind(':pin', $pin);
    $resultset = $db->resultset();
    $db->close();
    return count($resultset) > 0;
}

function lookupPinForCallSid($callsid)
{
    $db = new Database();
    $sql = sprintf("SELECT pin FROM sessions where callsid = :callsid order by timestamp desc limit 1");
    $db->query($sql);
    $db->bind(':callsid', $callsid);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
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
        $type = RecordType::PHONE;
    } elseif (isset($_REQUEST['SmsSid'])) {
        $callSid = $_REQUEST['SmsSid'];
        $type = RecordType::SMS;
    } else {
        return;
    }

    $meta_as_json = isset($meta) ? json_encode($meta) : null;

    $service_body_id = setting('service_body_id');

    $db = new Database();
    $stmt = "INSERT INTO `records_events` (`callsid`,`event_id`,`event_time`,`service_body_id`,`meta`, `type`) VALUES (:callSid, :eventid, :event_time, :service_body_id, :meta, :type)";
    $db->query($stmt);
    $db->bind(':callSid', $callSid);
    $db->bind(':eventid', $eventid);
    date_default_timezone_set('UTC');
    $db->bind(':event_time', getCurrentTime());
    $db->bind(':service_body_id', $service_body_id);
    $db->bind(':meta', $meta_as_json);
    $db->bind(':type', $type);
    $db->execute();
    $db->close();
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

function setConferenceParticipant($friendlyname, $callsid, $role)
{
    require_once 'twilio-client.php';
    $conferences = $GLOBALS['twilioClient']->conferences->read(array ("friendlyName" => $friendlyname ));
    $conferencesid = $conferences[0]->sid;
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

function setDatabaseCacheValue($key, $value, $expiry)
{
    $db = new Database();
    $stmt = "INSERT INTO `cache` (`key`, `value`, `expiry`) VALUES (:key, :value, :expiry)";
    $db->query($stmt);
    $db->bind(':key', $key);
    $db->bind(':value', $value);
    $db->bind(':expiry', $expiry);
    $db->execute();
    $db->close();
}

function getDatabaseCacheValue($key)
{
    $db = new Database();
    $stmt = "SELECT `value`,`expiry` FROM `cache` WHERE `key`=:key ORDER BY id DESC LIMIT 1";
    $db->query($stmt);
    $db->bind(':key', $key);
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}

function deleteExpiredCacheValues($currentEpoch)
{
    $db = new Database();
    $db->query("DELETE FROM `cache` WHERE `expiry` <= :currentEpoch");
    $db->bind(":currentEpoch", $currentEpoch);
    $db->execute();
    $db->close();
}

function clearCache()
{
    $db = new Database();
    $stmt = "TRUNCATE TABLE `cache`";
    $db->query($stmt);
    $db->execute();
    $db->close();
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
    $resultset = $db->resultset();
    $db->close();
    return $resultset;
}
