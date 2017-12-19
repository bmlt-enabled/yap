<?php
    include 'config.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($province_lookup) {
        $province = $_REQUEST['SpeechResult'];
    }
?>
<Response>
    <Gather input="speech" timeout="5000" speechTimeout="auto" action="voice-input-result.php?SearchType=<?php echo $searchType; ?>&Province=<?php echo $province?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Please say the name of the city or county.</Say>     
    </Gather>
</Response>
