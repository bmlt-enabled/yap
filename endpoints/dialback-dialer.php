<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo word('please_wait_while_we_connect_your_call'); ?>
    </Say>
    <Dial callerId="<?php echo $_GET['Called'] ?>">
        <?php echo str_replace("#", "", $_GET['Digits']); ?>
    </Dial>
</Response>
