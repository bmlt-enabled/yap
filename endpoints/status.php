<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$callRecord = new CallRecord();
$callRecord->callSid = $_REQUEST['CallSid'];
$callRecord->to_number = $_REQUEST['Called'];
$callRecord->from_number = $_REQUEST['Caller'];
$callRecord->duration = intval($_REQUEST['CallDuration']);
$twilioRecords = $twilioClient->calls($callRecord->callSid)->fetch();
$callRecord->start_time = $twilioRecords->startTime->getTime ->format("Y-m-d H:i:s");
$callRecord->end_time = $twilioRecords->endTime->format("Y-m-d H:i:s");
$callRecord->payload = json_encode($_REQUEST);

insertCallRecord($callRecord);
?>
<Response></Response>
