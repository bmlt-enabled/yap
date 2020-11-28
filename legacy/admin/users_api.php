<?php
require_once __DIR__ . '/../_includes/functions.php';
header("content-type: application/json");
$action = $_REQUEST['action'];
$data = json_decode(file_get_contents('php://input'));
if ($action === "save" && canManageUsers()) {
    saveUser($data);
} else if ($action === "edit" && canManageUsers()) {
    editUser($data, 'admin');
} else if ($action === "delete" && canManageUsers()) {
    deleteUser($data->id);
} else if ($action === "profile" && $_SESSION['auth_id'] === $data->id) {
    editUser($data, 'self');
}
