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
