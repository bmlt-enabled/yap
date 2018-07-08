<?php
    include 'functions.php';
    $searchType = $_REQUEST['Digits'];
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;
    
    if ($searchType == "1") {
        if (isset($_SESSION['ForcedHelplineServiceBodyId'])) {
            header("Location: helpline-search.php?Called=" . $_REQUEST["Called"]);
            exit();
        }

        $searchDescription = word('someone_to_talk_to');
    } else if ($searchType == "2") {
        $searchDescription = word('meetings');
    } else {
        header('Location: fetch-jft.php');
        exit();
    }

    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input-method-result.php?SearchType=<?php echo $searchType ?>" method="GET">
    
    	<?php 
			if ($playTitle == "1") {
				echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . setting("title") . "</Say>";
			}
		?>
       
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('press') ?> <?php echo word('one') ?> <?php echo word('to_search_for') ?> <?php echo $searchDescription ?> <?php echo word ('by') ?> <?php echo word('city_or_county') ?>
        </Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('press') ?> <?php echo word('two') ?> <?php echo word('to_search_for') ?> <?php echo $searchDescription ?> <?php echo word ('by') ?> <?php echo word('zip_code') ?>
        </Say>

        <?php
            if ($searchType == "2") {
                if (has_setting('jft_option') && setting('jft_option')) { ?>
                    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
                        <?php echo word('press') ?> <?php echo word('three') ?> <?php echo word('to_listen_to_the_just_for_today') ?>
                </Say>
                <?php }
            }
        ?>

    </Gather>
</Response>