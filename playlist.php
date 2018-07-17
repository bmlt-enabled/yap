<?php
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$playlist = explode(",", $_REQUEST["items"]);
?>
<Response>
<?php foreach($playlist as $item) { ?>
    <Play><?php echo $item?></Play>
<?php } ?>
    <Redirect>playlist.php?items=<?php echo $_REQUEST["items"]?></Redirect>
</Response>
