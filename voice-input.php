<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $searchType = $_REQUEST['SearchType'];
    $inputMethod = $_REQUEST['InputMethod'];
    
    if ($searchType == "1") {
        $action = "helpline-search.php";
    } else {
        $action = "meeting-searchtype.php";
    }
?>
<Response>
    <Gather input="speech" timeout="5000" action="<?php echo $action; ?>" method="GET">
<?php 
    if ($inputMethod == "1") {
?>
        <Say>Please say the name of the city.</Say>     
<?php
    } else {
?>
        <Say>Please say the name of the county.</Say>
<?php 
    }
?>
    </Gather>
</Response>
