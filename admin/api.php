<?php
require_once 'auth_verify.php';
header("content-type: application/json");
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    admin_PersistDbConfig(
        $_REQUEST['service_body_id'],
        file_get_contents('php://input'),
        $_REQUEST['data_type']
    );
}

$data = getDbData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
echo count($data) > 0 ? $data[0]['data'] : json_encode(new StdClass());
