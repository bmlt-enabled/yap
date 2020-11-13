<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";?>
<Response>
    <Redirect>helpline-search.php?override_service_body_id=<?php echo $_REQUEST['Digits']?></Redirect>
</Response>
