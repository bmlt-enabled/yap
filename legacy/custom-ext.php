<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>

<Response>
    <Gather language="<?php echo setting('gather_language') ?>" input="dtmf" timeout="15" finishOnKey="#" method="GET" action="custom-ext-dialer.php">
        <Play><?php echo setting(str_replace("-", "_", getWordLanguage()) . "_custom_extensions_greeting");?></Play>
    </Gather>
</Response>
