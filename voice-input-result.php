<?php
    include 'config.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $province = $province_lookup ? $_REQUEST['Province'] : getProvince();
    $speechResult = $_REQUEST['SpeechResult'];
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "meeting-searchtype.php";
    }
?>
<Response>
    <Redirect method="GET"><?php echo $action; ?>?Digits=<?php echo urlencode($speechResult . ", " . $province); ?></Redirect>
</Response>