<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>

<Response>
    <Gather language="<?php echo setting('gather_language') ?>" input="dtmf" timeout="15" finishOnKey="#" method="GET" action="dialback-dialer.php">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language')?>">Please enter the dialback pin, followed by the pound sign.</Say>
    </Gather>
</Response>
