<?php include_once 'nav.php';
admin_PersistHelplineData(0,
    $_REQUEST['service_body_id'],
    file_get_contents('php://input'));