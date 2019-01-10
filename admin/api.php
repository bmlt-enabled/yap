<?php
require_once 'auth_verify.php';
header("content-type: application/json");
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    if ($_REQUEST['data_type'] === DataType::YAP_CONFIG || $_REQUEST['data_type'] === DataType::YAP_DATA) {
        admin_PersistHelplineData($_REQUEST['helpline_data_id'],
            $_REQUEST['service_body_id'],
            file_get_contents('php://input'),
            $_REQUEST['data_type']
        );
    } else if ($_REQUEST['data_type'] === DataType::YAP_CONFIG_V2) {
        admin_PersistDbConfig(
            $_REQUEST['service_body_id'],
            file_get_contents('php://input'),
            $_REQUEST['data_type']
        );
    }
}

if ($_REQUEST['data_type'] === DataType::YAP_CONFIG || $_REQUEST['data_type'] === DataType::YAP_DATA) {
    $data = getHelplineData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
    echo json_encode(count($data) > 0 ? $data[0] : new StdClass());
} else if ($_REQUEST['data_type'] === DataType::YAP_CONFIG_V2) {
    $data = getDbData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
    echo $data;
}


