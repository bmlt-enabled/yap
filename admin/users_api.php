<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
$action = $_REQUEST['action'];
$data = json_decode(file_get_contents('php://input'));
if ($action === "save")
{
    saveUser($data);
}
else if ($action === "delete")
{
    deleteUser($data->id);
}
