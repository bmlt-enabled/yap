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
?>
<Response>
    <Gather numDigits="5" timeout="10" action="<?php echo $action; ?>" method="GET">
	<Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('please_enter_your_digit') ?> <?php echo word('zip_code') ?>
	</Say>
    </Gather>
</Response>
