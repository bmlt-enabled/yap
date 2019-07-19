<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = getSearchType('SearchType');
?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="speech" timeout="5" speechTimeout="auto" action="city-or-county-voice-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('please_say_the_name_of_the') ?> <?php echo word('state_or_province') ?>
        </Say>
    </Gather>
</Response>
