<?php
if (!file_exists('config.php')) {
    header(sprintf('Location: %s', str_contains($_SERVER['REQUEST_URI'], 'admin') ? 'installer.php' : 'admin/installer.php'), true, 302);
    exit();
}
if (isset($_GET["ysk"])) {
    session_id($_GET["ysk"]);
}
@session_start();
require_once 'migrations.php';
if (isset($_GET["CallSid"])) {
    insertSession($_GET["CallSid"]);
}
checkBlocklist();

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

class AuthMechanism
{
    const V1 = "_BMLT_AUTH_";
    const V2 = "_YAP_DB_AUTH_";
}

function checkBlocklist()
{
    if (has_setting('blocklist') && strlen(setting('blocklist')) > 0 && isset($_REQUEST['Caller'])) {
        $blocklist_items = explode(",", setting('blocklist'));
        foreach ($blocklist_items as $blocklist_item) {
            if (strpos($blocklist_item, $_REQUEST['Caller']) === 0) {
                header("content-type: text/xml");
                echo "<?xml version='1.0' encoding='UTF-8'?>\n<Response><Reject/></Response>";
                exit;
            }
        }
    }
}

function has_setting($name)
{
    return !is_null(setting($name));
}

function setting($name)
{
    if (isset($GLOBALS['settings_allowlist'][$name]) && $GLOBALS['settings_allowlist'][$name]['overridable']) {
        if (isset($_REQUEST[$name]) && $GLOBALS['settings_allowlist'][$name]['hidden'] !== true) {
            return $_REQUEST[$name];
        } else if (isset($_SESSION["override_" . $name])) {
            return $_SESSION["override_" . $name];
        }
    }

    if (isset($GLOBALS[$name])) {
        return $GLOBALS[$name];
    } else if (isset($GLOBALS['settings_allowlist'][$name]['default'])) {
        return $GLOBALS['settings_allowlist'][$name]['default'];
    }

    return null;
}

function getAdminBMLTRootServer()
{
    if (has_setting('helpline_bmlt_root_server')) {
        return setting('helpline_bmlt_root_server');
    } else {
        return setting('bmlt_root_server');
    }
}

function getServiceBodies()
{
    $bmlt_search_endpoint = sprintf('%s/client_interface/json/?switcher=GetServiceBodies', getAdminBMLTRootServer());
    return json_decode(get($bmlt_search_endpoint, false, 3600));
}

function getServiceBodiesRights()
{
    if (isset($_SESSION['auth_mechanism'])) {
        if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_permissions', getAdminBMLTRootServer());
            $service_bodies_for_user = json_decode(get($url, true));

            if ($service_bodies_for_user == null) {
                return null;
            }

            if (!is_array($service_bodies_for_user->service_body)) {
                $service_bodies_for_user = array($service_bodies_for_user->service_body);
            } elseif (isset($service_bodies_for_user->service_body)) {
                $service_bodies_for_user = $service_bodies_for_user->service_body;
            } else {
                $service_bodies_for_user = array();
            }

            $service_bodies = getServiceBodies();
            $enriched_service_bodies_for_user = array();
            foreach ($service_bodies_for_user as $service_body_for_user) {
                foreach ($service_bodies as $service_body) {
                    if (intval($service_body->id) === $service_body_for_user->id) {
                        array_push($enriched_service_bodies_for_user, $service_body);
                    }
                }
            }

            return $enriched_service_bodies_for_user;
        } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2 && $_SESSION['auth_is_admin']) {
            return getServiceBodies();
        } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2) {
            $service_bodies = getServiceBodies();
            $service_body_rights = $_SESSION['auth_service_bodies'];
            $service_bodies_for_user = array();
            foreach ($service_bodies as $service_body) {
                if (in_array($service_body->id, $service_body_rights)) {
                    array_push($service_bodies_for_user, $service_body);
                }
            }

            return $service_bodies_for_user;
        }
    }

    return null;
}

function getUserAgent()
{
    return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +yap';
}

function getBMLTAuthSessionCookies()
{
    return isset($_SESSION['bmlt_auth_session']) ? implode(";", $_SESSION['bmlt_auth_session']) : "";
}

function check_auth()
{
    if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
        if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, sprintf('%s/local_server/server_admin/xml.php?admin_action=get_permissions', getAdminBMLTRootServer()));
            curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
            curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
        } else {
            $res = "NOT AUTHORIZED";
        }

        return !preg_match('/NOT AUTHORIZED/', $res);
    } else {
        return true;
    }
}
