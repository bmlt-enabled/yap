<?php
require_once 'auth_verify.php';
header("content-type: application/json");
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
    $id = admin_PersistDbConfig(
        $_REQUEST['service_body_id'],
        file_get_contents('php://input'),
        $_REQUEST['data_type'],
        isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : "0"
    );
}

if (isset($parent_id) || isset($_REQUEST['parent_id'])) {
    $data = getDbDataByParentId(isset($parent_id) ? $parent_id : $_REQUEST['parent_id'], $_REQUEST['data_type']);
} else if ($_REQUEST['data_type'] === DataType::YAP_GROUPS_V2 && isset($id)) {
    $data = getDbDataById($id, $_REQUEST['data_type']);
} else {
    $data = getDbData($_REQUEST['service_body_id'], $_REQUEST['data_type']);
}

echo count($data) > 0 ? json_encode($data[0]) : json_encode(new StdClass());
