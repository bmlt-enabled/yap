<?php
    include 'config.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "meeting-searchtype.php";
    }
?>
<Response>
    <Gather numDigits="5" timeout="10" action="<?php echo $action; ?>" method="GET">
	<Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            Please enter your 5 digit zip code.
	</Say>
    </Gather>
</Response>
