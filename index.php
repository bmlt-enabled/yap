<?php
    include 'config.php';
    
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input-method.php" method="GET">
	<Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo $GLOBALS['title'] ?>
	</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 1 to find someone to talk to.</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 2 to find a meeting.</Say>
    </Gather>
</Response>