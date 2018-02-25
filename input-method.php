<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $searchType = $_REQUEST['Digits'];
    
    if ($searchType == "1") {
        $searchDescription = "someone to talk to";
    } else {
        $searchDescription = "meetings";
    }
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input-method-result.php?SearchType=<?php echo $searchType ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 1 to search for <?php echo $searchDescription ?> by city or county name.</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 2 to search for <?php echo $searchDescription ?> by zip code.</Say>
    </Gather>
</Response>