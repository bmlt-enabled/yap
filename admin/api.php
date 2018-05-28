<?php
include_once 'auth_verify.php';
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    admin_PersistHelplineData($_REQUEST['helpline_data_id'],
        $_REQUEST['service_body_id'],
        file_get_contents('php://input')
    );
}

echo json_encode(getHelplineData($_REQUEST['service_body_id']));


