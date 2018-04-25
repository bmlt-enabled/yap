<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($province_lookup) {
        $province = $_REQUEST['SpeechResult'];
    } else {
        $province = "";
    }
?>
<Response>
    <Gather language="<?php echo getGatherLanguage(); ?>" hints="<?php echo getGatherHints(); ?>" input="speech" timeout="5" speechTimeout="auto" action="voice-input-result.php?SearchType=<?php echo $searchType; ?>&amp;Province=<?php echo urlencode($province)?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>"><?php echo word('please_say_the_name_of_the')?> <?php echo word('city_or_county')?></Say>
    </Gather>
</Response>
