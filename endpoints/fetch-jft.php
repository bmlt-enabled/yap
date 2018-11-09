<?php
require_once 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$jft_array = get_jft();
?>
<Response>
    <?php
        foreach ($jft_array as $item)  {
            if (trim($item) != "") {
                echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">"
                     . $item . "</Say>";
            }
        }
    ?>
    <Hangup />
</Response>
