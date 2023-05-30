<?php
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
