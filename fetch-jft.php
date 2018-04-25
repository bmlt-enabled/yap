<?php
include 'functions.php';

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$result = get("http://www.jftna.org/jft/");
$stripped_results = strip_tags($result);
$without_tabs = str_replace("\t", "", $stripped_results);
$final_array = explode("\n", $without_tabs);
?>
<Response>
    <?php
        foreach ($final_array as $item)  {
            if ($item != "") {
                echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">"
                     . html_entity_decode($item) . "</Say>";
            }
        }
    ?>
    <Hangup />
</Response>
