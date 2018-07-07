<?php
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
    <Say>Please leave a message after the tone, press pound or hang up when finished.</Say>
    <Record
        playBeep="true"
        recordingStatusCallback="voicemail-complete.php"
        recordingStatusCallbackMethod="GET"
        finishOnKey="#"
        maxLength="120"
        timeout="15"/>
    <Hangup/>
</Response>
