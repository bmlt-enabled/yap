<?php
    include 'functions.php';
    $searchType = $_REQUEST['Digits'];
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;
    
    if ($searchType == "1") {
    if (isset($GLOBALS['helpline_direct_location']) && $GLOBALS['helpline_direct_location']) {
      header("Location: helpline-search.php?Digits=" .$GLOBALS['helpline_direct_location']);
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
				echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $GLOBALS['title'] . "</Say>";
			}
		?>
       
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('press') ?> <?php echo word('one') ?> <?php echo word('to_search_for') ?> <?php echo $searchDescription ?> <?php echo word ('by') ?> <?php echo word('city_or_county') ?>
        </Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('press') ?> <?php echo word('two') ?> <?php echo word('to_search_for') ?> <?php echo $searchDescription ?> <?php echo word ('by') ?> <?php echo word('zip_code') ?>
        </Say>
    </Gather>
</Response>