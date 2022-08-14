<?php
require_once __DIR__ . '/../_includes/functions.php';
unset($_SESSION['call_state']);
$auth_v2_result = auth_v2($_POST['username'], $_POST['password']);
if (count($auth_v2_result) == 1) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_user_name_string'] = $auth_v2_result[0]['name'];
    $_SESSION['auth_is_admin'] = $auth_v2_result[0]['is_admin'];
    $_SESSION['auth_permissions'] = $auth_v2_result[0]['permissions'];
    $_SESSION['auth_id'] = $auth_v2_result[0]['id'];
    $_SESSION['auth_service_bodies'] = explode(",", $auth_v2_result[0]['service_bodies']);

    // TODO: this provides backward compatability until the models are migrated to Laravel.
    $_SESSION['auth_service_bodies_rights'] = getServiceBodiesRightsIds();
    setConfigForService($_SESSION['auth_service_bodies_rights'][0]);

    header('Location: home.php');
} elseif (setting("bmlt_auth") && auth_v1($_POST['username'], $_POST['password'])) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['auth_mechanism'] = AuthMechanism::V1;

    // TODO: this provides backward compatability until the models are migrated to Laravel.
    $_SESSION['auth_service_bodies_rights'] = getServiceBodiesRightsIds();
    setConfigForService($_SESSION['auth_service_bodies_rights'][0]);

    header('Location: home.php');
} else {
    header('Location: auth/invalid');
}
exit();
