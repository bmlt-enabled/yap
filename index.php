<?php
    include 'config.php';
    
    if ($_REQUEST['override'] == "1") {
        header("Location: input-method.php?Digits=2");
    } else {
        header("content-type: text/xml");
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="1" timeout="10000" action="input-method.php" method="GET">
	<Say>
            <?php echo $GLOBALS['title'] ?>
	</Say>
        <Say>Press 1 to find someone to talk to.</Say>
        <Say>Press 2 to find a meeting.</Say>
    </Gather>
</Response>
<?php
    }
?>