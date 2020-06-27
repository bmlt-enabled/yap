<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

$auth_v2_result = auth_v2($_POST['username'], $_POST['password']);
if (count($auth_v2_result) == 1) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_user_name_string'] = $auth_v2_result[0]['name'];
    $_SESSION['auth_is_admin'] = $auth_v2_result[0]['is_admin'];
    $_SESSION['auth_service_bodies'] = explode(",", $auth_v2_result[0]['service_bodies']);
    $_SESSION['auth_permissions'] = $auth_v2_result[0]['permissions'];
    $_SESSION['auth_id'] = $auth_v2_result[0]['id'];
    header('Location: home.php');
    exit();
} elseif (setting("bmlt_auth") && auth_v1($_POST['username'], $_POST['password'])) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V1;
    header('Location: home.php');
    exit();
} else {
    logout_auth($_POST['username']);
    header('Location: index.php?auth=false');
    exit();
}
