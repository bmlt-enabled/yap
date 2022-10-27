<?php
require_once '_includes/functions.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
    <Dial callerId="<?php echo $_GET['Called'] ?>">
        <?php echo setting('custom_extensions')[str_replace("#", "", $_GET['Digits'])]; ?>
    </Dial>
</Response>
