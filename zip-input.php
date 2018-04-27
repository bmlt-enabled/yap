<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "address-lookup.php";
    }

    $postal_code_length = isset($GLOBALS['postal_code_length']) ? $GLOBALS['postal_code_length'] : "5";
?>
<Response>
    <Gather numDigits="<?php echo $postal_code_length?>" timeout="10" action="<?php echo $action; ?>" method="GET">
	<Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('please_enter_your_digit') ?> <?php echo word('zip_code') ?>
	</Say>
    </Gather>
</Response>
