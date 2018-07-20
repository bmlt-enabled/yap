<?php
    include 'functions.php';
    $inputMethod = $_REQUEST['Digits'];

    if ($inputMethod == "3") {
        header('Location: fetch-jft.php');
        exit();
    }

    $searchType = $_REQUEST['SearchType'];
    if (has_setting('province_lookup') && setting('province_lookup') ) {
        $action = "province-voice-input.php";
    } else {
        $action = "city-or-county-voice-input.php";
    }

    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php
    if ($inputMethod == "1") { // city or county
?>
    <Redirect method="GET"><?php echo $action; ?>?SearchType=<?php echo $searchType; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
<?php
    } else { // zip
?>
    <Redirect method="GET">zip-input.php?SearchType=<?php echo $searchType; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
<?php
    }
?>
</Response>