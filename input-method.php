<?php
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
    <Gather numDigits="1" timeout="10000" action="input-method-redirect.php?SearchType=<?php echo $searchType ?>" method="GET">
        <Say>Press 1 to search for <?php echo $action ?> by city name.</Say>
        <Say>Press 2 to search for <?php echo $action ?> by county name.</Say>
        <Say>Press 3 to search for <?php echo $action ?> by zip code.</Say>
    </Gather>
</Response>