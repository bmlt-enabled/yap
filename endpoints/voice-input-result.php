<?php
    require_once 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $province = has_setting('province_lookup') && json_decode(setting('province_lookup')) ? $_REQUEST['Province'] : getProvince();
    $speechResult = $_REQUEST['SpeechResult'];
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "address-lookup.php";
    }
?>
<Response>
    <Redirect method="GET"><?php echo $action; ?>?Digits=<?php echo urlencode($speechResult . ", " . $province); ?>&amp;SearchType=<?php echo $searchType?></Redirect>
</Response>
