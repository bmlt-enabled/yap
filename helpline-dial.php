<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>   
<Response>
    <Say>Please wait while we connect your call...</Say>
    <Enqueue><?php echo $_REQUEST['queue'] ?></Enqueue>
</Response>
