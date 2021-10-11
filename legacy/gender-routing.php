<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="<?php echo getInputType() ?>" timeout="10" speechTimeout="auto" action="gender-routing-response.php?SearchType=<?php echo $_REQUEST['SearchType']?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php
                $gender_no_preference = (setting("gender_no_preference") ? sprintf(", %s %s %s", word("press"), word('three'), word('speak no preference')) : "");
                echo sprintf("%s %s %s, %s %s %s%s", word('press'), word('one'), word('to speak to a man'), word('press'), word('two'), word('to speak to a woman'), $gender_no_preference)
            ?>
        </Say>
    </Gather>
</Response>
