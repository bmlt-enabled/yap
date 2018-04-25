<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
?>
<Response>
    <Gather language="<?php echo getGatherLanguage(); ?>" hints="<?php echo getGatherHints(); ?>" input="speech" timeout="5" speechTimeout="auto" action="city-or-county-voice-input.php?SearchType=<?php echo $searchType; ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('please_say_the_name_of_the') ?> <?php echo word('state_or_province') ?>
        </Say>
    </Gather>
</Response>
