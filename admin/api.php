<?php
include_once 'auth_verify.php';
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    admin_PersistHelplineData($_REQUEST['helpline_data_id'],
        $_REQUEST['service_body_id'],
        file_get_contents('php://input'),
        $_REQUEST['data_type']
    );
}

$data = getHelplineData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
echo json_encode(count($data) > 0 ? $data[0] : new StdClass());
