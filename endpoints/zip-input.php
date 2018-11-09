<?php
    require_once 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "address-lookup.php";
    }

    $action .= "?SearchType=" . $searchType;
?>
<Response>
    <Gather input="speech dtmf" numDigits="<?php echo setting('postal_code_length')?>" timeout="10" speechTimeout="auto" action="<?php echo $action; ?>" method="GET">
	<Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('please_enter_your_digit') ?> <?php echo word('zip_code') ?>
	</Say>
    </Gather>
</Response>
