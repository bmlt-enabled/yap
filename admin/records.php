<?php require_once 'nav.php';
include_once '../endpoints/_includes/twilio-client.php';?>
<div class="container">
    <h3>Volunteer Records</h3>
    <table border="1">
        <tr><th>Conference Id</th><th>Recordings</th><th>Duration (in seconds)</th><th>Participant Id</th><th>Phone Number</th></thd><th>Role</th><th>Timestamp</th></tr>
        <?php
        $rows = getConferences($_REQUEST['service_body_id']);
        $conferences = [];
        foreach ($rows as $row) {
            $participant = $twilioClient->calls($row['callsid'])->fetch();
            $role = $participant->direction == 'outbound-api' ? "volunteer" : "caller";
            $phone_number = $role == "volunteer" ? $participant->to : $participant->from;

            if (isset($lastconferencesid) && $row['conferencesid'] == $lastconferencesid) {
                echo "<tr><td colspan='3'></td><td>" . $row['callsid'] . "</td><td>$phone_number</td><td>$role</td><td>" . $row['timestamp'] . "</td></tr>";
            } else {
                $conference = $twilioClient->conferences($row['conferencesid'])->fetch();
                $recordings = $participant->recordings->read();
                $recordingUri = count($recordings) > 0 ? "<a href='https://api.twilio.com" . str_replace(".json", ".mp3", $recordings[0]->uri) . "' target='_blank'>Play</a>" : "";
                echo "<tr><td>" . $row['conferencesid'] . "</td><td>$recordingUri</a></td><td>" . ($conference->dateCreated)->diff($conference->dateUpdated)->s . "</td><td>" . $row['callsid'] . "</td><td>$phone_number</td><td>$role</td><td>" . $row['timestamp'] . "</td></tr>";
            }
            $lastconferencesid = $row['conferencesid'];
        }
        ?>
    </table>
</div>
<?php
require_once 'footer.php';

