<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
$action = $_REQUEST['action'];
$data = json_decode(file_get_contents('php://input'));
if ($action === "save" && boolval($_SESSION['auth_is_admin'])) {
    saveUser($data);
} else if ($action === "edit" && boolval($_SESSION['auth_is_admin'])) {
    editUser($data, 'admin');
} else if ($action === "delete" && boolval($_SESSION['auth_is_admin'])) {
    deleteUser($data->id);
} else if ($action === "profile" && $_SESSION['auth_id'] === $data->id) {
    editUser($data, 'self');
}
