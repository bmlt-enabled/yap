<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $searchType = $_REQUEST['Digits'];
    
    if ($searchType == "1") {
        $action = "someone to talk to";
    } else {
        $action = "meetings";
    }
?>
<Response>
    <Gather numDigits="1" timeout="10000" action="input-method-result.php?SearchType=<?php echo $searchType ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 1 to search for <?php echo $action ?> by city or county name.</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 2 to search for <?php echo $action ?> by zip code.</Say>
    </Gather>
</Response>