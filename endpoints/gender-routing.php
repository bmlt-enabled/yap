<?php
require_once 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="speech dtmf" timeout="5" speechTimeout="auto" action="gender-routing-response.php?SearchType=<?php echo $_GET['SearchType']?>&amp;Address=<?php echo urlencode($_GET['Address'])?>" method="GET">
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">Press 1 to speak to a man, Press 2 to speak to a woman.</Say>
    </Gather>
</Response>
