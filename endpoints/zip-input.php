<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "address-lookup.php";
    }

    $enterWord = has_setting('speech_gathering') && json_decode(setting('speech_gathering')) ? word('please_enter_or_say_your_digit') : word('please_enter_your_digit');

    $action .= "?SearchType=" . $searchType;
?>
<Response>
    <Gather input="<?php echo getInputType() ?>" numDigits="<?php echo setting('postal_code_length')?>" timeout="10" speechTimeout="auto" action="<?php echo $action; ?>" method="GET">
	<Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo $enterWord ?> <?php echo word('zip_code') ?>
	</Say>
    </Gather>
</Response>
