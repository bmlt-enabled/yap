<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

$auth_v2_result = auth_v2($_POST['username'], $_POST['password']);
if (count($auth_v2_result) == 1) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['is_admin'] = $auth_v2_result[0]['is_admin'];
    $_SESSION['permissions'] = $auth_v2_result[0]['permissions'];
    header('Location: home.php');
    exit();
} elseif (auth_v1($_POST['username'], $_POST['password'])) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V1;
    header('Location: home.php');
    exit();
} else {
    logout_auth($_POST['username']);
    header('Location: index.php?auth=false');
    exit();
}
