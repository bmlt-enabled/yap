<?php
    include 'config.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    $inputMethod = $_REQUEST['InputMethod'];
?>
<Response>
    <Gather input="speech" timeout="5000" speechTimeout="auto" action="city-or-county-voice-input.php?SearchType=<?php echo $searchType; ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Please say the name of the state or province.</Say>     
    </Gather>
</Response>
