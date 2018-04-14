<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $searchType = $_REQUEST['Digits'];
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;
    
    if ($searchType == "1") {
        $searchDescription = "someone to talk to";
    } else if ($searchType == "2") {
        $searchDescription = "meetings";
    } else {
        header('Location: fetch-jft.php');
        exit();
    }
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input-method-result.php?SearchType=<?php echo $searchType ?>" method="GET">
    
    	<?php 
			if ($playTitle == "1") {
				echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $GLOBALS['title'] . "</Say>";
			}
		?>
       
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 1 to search for <?php echo $searchDescription ?> by city or county name.</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 2 to search for <?php echo $searchDescription ?> by zip code.</Say>
    </Gather>
</Response>