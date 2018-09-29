<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $address = $_REQUEST['Body'];
    $coordinates = getCoordinatesForAddress($address . "," . getProvince());
?>
<Response>
<?php
    $sms_helpline_keyword = setting("sms_helpline_keyword");
    if (str_exists(strtoupper($address), strtoupper($sms_helpline_keyword))) {
        if (strlen(trim(str_replace(strtoupper($sms_helpline_keyword), "", strtoupper($address)))) > 0) {?>
            <Redirect method="GET">helpline-sms.php?OriginalCallerId=<?php echo $_REQUEST['From']?>&amp;To=<?php echo $_REQUEST['To']?>&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
        } else {
?>
        <Sms><?php echo word('please_send_a_message_formatting_as') ?> "<?php echo $sms_helpline_keyword?>", <?php echo word('followed_by_your_location')?>, <?php echo word('for') ?> <?php echo word('someone_to_talk_to')?>.</Sms>
<?php   }
    } 
	else if (str_exists(strtoupper($address), strtoupper('jft'))) {
         $result = get("http://www.jftna.org/jft/");
         $stripped_results = strip_tags($result);
         $without_tabs = str_replace("\t", "", $stripped_results);
	     $without_htmlentities = html_entity_decode($without_tabs);
	     $without_extranewlines = preg_replace("/[\r\n]+/", "\n\n", $without_htmlentities); ?>
         <Sms><?php  echo $without_extranewlines; ?> </Sms>
 <?php } 
	else {
?>
    <Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
    }
?>
</Response>
