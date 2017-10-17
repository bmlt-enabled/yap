<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $inputMethod = $_REQUEST['Digits'];
    $searchType = $_REQUEST['SearchType'];
?>
<Response>
<?php
    if ($inputMethod == "1") { // city or county
?>
    <Redirect method="GET">voice-input.php?SearchType=<?php echo $searchType; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
<?php
    } else { // zip
?>
    <Redirect method="GET">zip-input.php?SearchType=<?php echo $searchType; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
<?php
    }
?>
</Response>