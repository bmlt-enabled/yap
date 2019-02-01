<?php include_once 'functions.php';?>
<html>
<body>
<table border="1" width="100%">
    <?php
    $meeting_results = getMeetings($_REQUEST['latitude'], $_REQUEST['longitude'], 50, null, null);
    foreach ($meeting_results->filteredList as $meeting) { ?>
        <tr><td style="font-size: 24px;">
                <?php
                $results = getResultsString($meeting);
                foreach ($results as $result) { ?>
                    <?php echo $result . "<br/>"; ?>
                    <?php
                }
                ?>
                <a href="https://google.com/maps?q=<?php echo $meeting->latitude?>,<?php echo $meeting->longitude?>" target="_blank">Map</a>
            </td></tr>
        <?php
    }
    ?>
</table>
</body>
</html>
