<?php
require_once 'auth_verify.php';
header("content-type: application/json");
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    $data = file_get_contents('php://input');

    if ($_REQUEST['data_type'] === DataType::YAP_GROUPS_V2 && isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
        admin_PersistDbConfigById($_REQUEST['id'], $data);
        $id = $_REQUEST['id'];
    } else {
        $id = admin_PersistDbConfig(
            $_REQUEST['service_body_id'],
            $data,
            $_REQUEST['data_type'],
            isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : "0"
        );
    }
}

if (isset($parent_id) || isset($_REQUEST['parent_id'])) {
    $data = getDbDataByParentId(isset($parent_id) ? $parent_id : $_REQUEST['parent_id'], $_REQUEST['data_type']);
} else if ($_REQUEST['data_type'] === DataType::YAP_GROUPS_V2 && isset($id)) {
    $data = getDbDataById($id, $_REQUEST['data_type']);
} else {
    $data = getDbData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
}

if (count($data) > 0) {
    echo sprintf(
        "{\"service_body_id\":%s,\"id\":%s,\"parent_id\":%s,\"data\":%s}",
        $data[0]['service_body_id'],
        $data[0]['id'],
        isset($data[0]['parent_id']) ? $data[0]['parent_id'] : "null",
        $data[0]['data']
    );
} else {
    echo "{}";
}
