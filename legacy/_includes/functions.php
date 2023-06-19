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
$GLOBALS['version']  = "4.3.0";
checkBlocklist();

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

class AlertId
{
    const STATUS_CALLBACK_MISSING = 1;
}

class AuthMechanism
{
    const V1 = "_BMLT_AUTH_";
    const V2 = "_YAP_DB_AUTH_";
}

class CurlException extends Exception
{
}

class UpgradeAdvisor
{
    private static $all_good = true;
    private static $email_settings = [
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_secure',
        'smtp_from_address',
        'smtp_from_name'
    ];

    private static function isThere($setting)
    {
        return isset($GLOBALS[$setting]) && strlen($GLOBALS[$setting]) > 0;
    }

    public static function getState($status = null, $message = null, $warnings = "")
    {
        try {
            $build = file_get_contents("build.txt", false);
        } catch (Exception $e) {
            $build = $e->getMessage();
        }
        return ["status"=>$status, "message"=>$message, "warnings"=>$warnings, "version"=>$GLOBALS['version'], "build"=>str_replace("\n", "", $build)];
    }

    public static function isAllowedError($exceptionName)
    {
        if (isset($GLOBALS["exclude_errors_on_login_page"]) && isset($_REQUEST['run_exclude_errors_check'])) {
            return !in_array($exceptionName, $GLOBALS["exclude_errors_on_login_page"]);
        }

        return true;
    }

    public static function getStatus()
    {
        $warnings = "";
//        foreach ($GLOBALS['required_config_settings'] as $setting) {
//            if (!self::isThere($setting)) {
//                return self::getState(false, "Missing required setting: " . $setting);
//            }
//        }

        $root_server_settings = json_decode(get(sprintf('%s/client_interface/json/?switcher=GetServerInfo', getAdminBMLTRootServer()), false, 3600));

        if (strpos(getAdminBMLTRootServer(), 'index.php')) {
            return self::getState(false, "Your root server points to index.php. Please make sure to set it to just the root directory.", $warnings);
        }

        if (!isset($root_server_settings)) {
            return self::getState(false, "Your root server returned no server information.  Double-check that you have the right root server url.", $warnings);
        } else {
            if ($root_server_settings[0]->semanticAdmin === "0") {
                return self::getState(false, "Your root server has semanticAdmin disabled, please enable it.  https://bmlt.app/semantic/semantic-administration/", $warnings);
            }
        }

        foreach (setting("digit_map_search_type") as $digit => $value) {
            if ($digit === 0) {
                return self::getState(false, "You cannot use 0 as an option for `digit_map_search_type`.", $warnings);
            }
        }

        try {
            $googleapi_settings = json_decode(get(sprintf("%s&address=91409", $GLOBALS['google_maps_endpoint']), false, 3600));

            if ($googleapi_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $googleapi_settings->error_message. " Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings);
            }

            $timezone_settings = json_decode(get(sprintf("%s&location=34.2011137,-118.475058&timestamp=%d", $GLOBALS['timezone_lookup_endpoint'], time() - (time() % 1800)), false));

            if ($timezone_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $timezone_settings->errorMessage. " Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings);
            }
        } catch (CurlException $e) {
            return self::getState(false, "HTTP Error connecting to Google Maps API, check your network settings.", $warnings);
        }

        $alerts = getMisconfiguredPhoneNumbersAlerts(AlertId::STATUS_CALLBACK_MISSING);
        if (count($alerts) > 0) {
            $misconfiguredPhoneNumbers = [];
            foreach ($alerts as $alert) {
                array_push($misconfiguredPhoneNumbers, $alert['payload']);
            }

            $warnings = sprintf("%s is/are phone numbers that are missing Twilio Call Status Changes Callback status.php webhook. This will not allow call reporting to work correctly.  For more information review the documentation page https://github.com/bmlt-enabled/yap/wiki/Call-Detail-Records.", implode(",", $misconfiguredPhoneNumbers));
        }

        try {
            foreach ($GLOBALS['twilioClient']->incomingPhoneNumbers->read() as $number) {
                if (basename($number->voiceUrl)) {
                    if (!strpos($number->voiceUrl, '.php')
                        && !strpos($number->voiceUrl, 'twiml')
                        && !strpos($number->voiceUrl, '/?')
                        && substr($number->voiceUrl, -1) !== "/") {
                        return self::getState(false, $number->phoneNumber . " webhook should end either with `/` or `/index.php`", $warnings);
                    }
                }
            }
        } catch (\Twilio\Exceptions\RestException $e) {
            return self::getState(false, "Twilio Rest Error: " . $e->getMessage(), $warnings);
        } catch (\Twilio\Exceptions\ConfigurationException $e) {
            if (self::isAllowedError("twilioMissingCredentials")) {
                return self::getState(false, "Twilio Configuration Error: " . $e->getMessage(), $warnings);
            }
        }

        if (has_setting('smtp_host')) {
            foreach (self::$email_settings as $setting) {
                if (!self::isThere($setting)) {
                    return self::getState(false, "Missing required email setting: " . $setting, $warnings);
                }
            }
        }

        if (isset($GLOBALS['mysql_hostname'])) {
            try {
                $db = new Database();
                $db->close();
            } catch (PDOException $e) {
                return self::getState(false, $e->getMessage(), $warnings);
            }
        }

        if (UpgradeAdvisor::$all_good) {
            return UpgradeAdvisor::getState(true, "Ready To Yap!", $warnings);
        }
    }
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
