<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Utilities\Sort;
use CurlException;
use Exception;
use Illuminate\Support\Facades\App;

class RootServerService extends Service
{
    protected HttpService $http;

    public function __construct(HttpService $http)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->http = $http;
    }

    public function getServiceBodies()
    {
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetServiceBodies',
            $this->settings->getAdminBMLTRootServer()
        );
        return json_decode($this->http->get($bmlt_search_endpoint, 15));
    }

    public function getServiceBodiesRightsIds()
    {
        $ids = [];

        foreach ($this->getServiceBodiesRights() as $service_body) {
            array_push($ids, $service_body->id);
        }

        return $ids;
    }

    public function getServiceBodiesForUser($include_general = false)
    {
        $service_bodies = $this->getServiceBodiesRights();

        if (isset($service_bodies)) {
            foreach ($service_bodies as $service_body) {
                $parent_service_body = $this->getServiceBody($service_body->parent_id);
                $service_body->parent_name = isset($parent_service_body) ? $parent_service_body->name : "None";
            }

            if ($include_general) {
                $service_bodies[] = (object)[
                    "id" => "0",
                    "name" => "All"
                ];
            }
        } else {
            $service_bodies = [];
        }

        Sort::sortOnField($service_bodies, 'name');
        return $service_bodies;
    }

    public function getServiceBodiesForUserRecursively($service_body_id, $service_body_rights = null): array
    {
        $service_bodies_results = [];

        if ($service_body_rights == null) {
            $service_bodies_results[] = intval($service_body_id);
            $service_body_rights = $this->getServiceBodiesForUser();
        }

        foreach ($service_body_rights as $service_body) {
            if (intval($service_body->parent_id) === intval($service_body_id)) {
                $service_bodies_results[] = intval($service_body->id);
                $service_bodies_results = array_merge(
                    $service_bodies_results,
                    $this->getServiceBodiesForUserRecursively(intval($service_body->id), $service_body_rights)
                );
            }
        }

        return $service_bodies_results;
    }

    public function getServiceBodiesRights()
    {
        if (isset($_SESSION['auth_mechanism'])) {
            if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
                $url = sprintf(
                    '%s/local_server/server_admin/json.php?admin_action=get_permissions',
                    $this->settings->getAdminBMLTRootServer()
                );
                $service_bodies_for_user = json_decode($this->http->getWithAuth($url));

                if ($service_bodies_for_user == null) {
                    return array();
                }

                if (!is_array($service_bodies_for_user->service_body)) {
                    $service_bodies_for_user = array($service_bodies_for_user->service_body);
                } elseif (isset($service_bodies_for_user->service_body)) {
                    $service_bodies_for_user = $service_bodies_for_user->service_body;
                } else {
                    $service_bodies_for_user = array();
                }

                $service_bodies = $this->getServiceBodies();
                $enriched_service_bodies_for_user = array();
                foreach ($service_bodies_for_user as $service_body_for_user) {
                    foreach ($service_bodies as $service_body) {
                        if (intval($service_body->id) === $service_body_for_user->id) {
                            $enriched_service_bodies_for_user[] = $service_body;
                        }
                    }
                }

                return $enriched_service_bodies_for_user;
            } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2 && $_SESSION['auth_is_admin']) {
                return $this->getServiceBodies();
            } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2) {
                $service_bodies = $this->getServiceBodies();
                $service_body_rights = $_SESSION['auth_service_bodies'];
                $service_bodies_for_user = array();
                foreach ($service_bodies as $service_body) {
                    if (in_array($service_body->id, $service_body_rights)) {
                        $service_bodies_for_user[] = $service_body;
                    }
                }

                return $service_bodies_for_user;
            }
        }

        return null;
    }

    public function getServiceBody($service_body_id)
    {
        foreach ($this->getServiceBodies() as $service_body) {
            if ($service_body->id == $service_body_id) {
                return $service_body;
            }
        }

        return null;
    }

    public function searchForMeetings($latitude, $longitude, $day)
    {
        $bmlt_base_url = sprintf(
            '%s/client_interface/json/?switcher=GetSearchResults&get_used_formats&data_field_key=id_bigint,meeting_name,weekday_tinyint,start_time,location_text,location_info,location_municipality,location_province,location_street,longitude,latitude,distance_in_miles,distance_in_km,formats,virtual_meeting_link,phone_meeting_number,virtual_meeting_additional_info',
            $this->settings->getBMLTRootServer()
        );
        $bmlt_search_endpoint = $this->settings->get('custom_query');
        if ($this->settings->has('ignore_formats')) {
            $bmlt_search_endpoint .= $this->getFormatString($this->settings->get('ignore_formats'), true);
        }

        if (json_decode($this->settings->get('include_unpublished'))) {
            $bmlt_search_endpoint .= "&advanced_published=0";
        }

        $magic_vars = ["{LATITUDE}", "{LONGITUDE}", "{DAY}"];
        $magic_swap = [$latitude, $longitude, $day];
        $custom_magic_vars = [];
        preg_match('/(\{SETTING_.*\})/U', $bmlt_search_endpoint, $custom_magic_vars);
        foreach ($custom_magic_vars as $custom_magic_var) {
            $magic_vars[] = $custom_magic_var;
            $magic_swap[] = $this->settings->get(
                strtolower(preg_replace('/(\{SETTING_(.*)\})/U', "$2", $custom_magic_var))
            );
        }

        $search_url = str_replace($magic_vars, $magic_swap, $bmlt_search_endpoint);
        $final_url = $bmlt_base_url . $search_url;

        try {
            $search_response = json_decode($this->http->get($final_url));
        } catch (Exception $e) {
            if ($e->getMessage() == "Couldn't resolve host name") {
                throw $e;
            } else {
                $search_response = "[]";
            }
        }

        return $search_response;
    }

    public function getServiceBodiesForRouting($latitude, $longitude)
    {
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetServiceBodies',
            $this->getHelplineRoutingBMLTServer($latitude, $longitude)
        );
        return json_decode($this->http->get($bmlt_search_endpoint, 3600));
    }

    private function getFormatString($formats, $ignore = false): string
    {
        $formatsArray = $this->getIdsFormats($formats);
        $finalString = "";
        for ($i = 0; $i < count($formatsArray); $i++) {
            $finalString .= "&formats[]=" . ($ignore ? "-" : "") . $formatsArray[$i];
        }

        return $finalString;
    }

    private function getIdsFormats($types): array
    {
        $typesArray = explode(",", $types);
        $finalFormats = array();
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetFormats',
            $this->settings->getBMLTRootServer()
        );
        $formats = json_decode($this->http->get($bmlt_search_endpoint, 3600));
        for ($t = 0; $t < count($typesArray); $t++) {
            for ($f = 0; $f < count($formats); $f ++) {
                if ($formats[ $f ]->key_string == $typesArray[$t]) {
                    $finalFormats[] = $formats[$f]->id;
                }
            }
        }

        return $finalFormats;
    }

    public function helplineSearch($latitude, $longitude, $helplineSearchRadius, $callRoutingFilter)
    {
        $search_url = sprintf(
            "%s/client_interface/json/?switcher=GetSearchResults&data_field_key=longitude,latitude,service_body_bigint&sort_results_by_distance=1&lat_val=%s&long_val=%s&geo_width=%s%s",
            $this->getHelplineRoutingBMLTServer($latitude, $longitude),
            $latitude,
            $longitude,
            $helplineSearchRadius,
            $callRoutingFilter,
        );

        return json_decode($this->http->get($search_url, 60));
    }

    public function getHelplineRoutingBMLTServer($latitude, $longitude)
    {
        if (json_decode($this->settings->get('tomato_helpline_routing'))
            && !$this->isBMLTServerOwned($latitude, $longitude)) {
            return $this->settings->get('tomato_url');
        } else {
            return $this->settings->getAdminBMLTRootServer();
        }
    }

    public function isServiceBodyHelplingCallInternallyRoutable($latitude, $longitude): bool
    {
        return !json_decode($this->settings->get('tomato_helpline_routing'))
            || $this->isBMLTServerOwned($latitude, $longitude);
    }

    public function isBMLTServerOwned($latitude, $longitude): bool
    {
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetSearchResults&data_field_key=root_server_uri&sort_results_by_distance=1&lat_val=%s&long_val=%s&geo_width=%s',
            $this->settings->get('tomato_url'),
            $latitude,
            $longitude,
            $this->settings->get('helpline_search_radius')
        );
        $search_results = json_decode($this->http->get($bmlt_search_endpoint, 60));
        $root_server_uri_from_first_result = $search_results[0]->root_server_uri;
        return str_contains($root_server_uri_from_first_result, $this->settings->getAdminBMLTRootServer());
    }

    public function getServerInfo()
    {
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetServerInfo',
            $this->settings->getAdminBMLTRootServer()
        );
        return json_decode($this->http->get($bmlt_search_endpoint, 3600));
    }

    public function authenticate($username, $password) : bool
    {
        $endpoint = ($this->settings->has('alt_auth_method') && $this->settings->get('alt_auth_method') ? '/index.php' : '/local_server/server_admin/xml.php');
        $baseUrl = sprintf("%s%s", $this->settings->getAdminBMLTRootServer(), $endpoint);
        $res = $this->http->postAsForm(
            $baseUrl,
            [
                "admin_action" => "login",
                "c_comdef_admin_login" => $username,
                "c_comdef_admin_password" => $password
            ]
        );
        $is_authed = preg_match('/^OK$/', str_replace(array("\r", "\n"), '', $res->body())) == 1;
        $_SESSION["bmlt_auth_session"] = $is_authed ? $this->getCookiesFromHeaders($res->cookies()) : null;
        return $is_authed;
    }

    public function logout()
    {
        $endpoint = 'local_server/server_admin/xml.php?admin_action=logout';
        $this->http->getWithAuth(sprintf("%s%s", $this->settings->getAdminBMLTRootServer(), $endpoint));
    }

    public function getLoggedInUsername()
    {
        if (!isset($_SESSION['auth_user_name_string'])) {
            $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_user_info', $this->settings->getAdminBMLTRootServer());
            $get_user_info_response = json_decode($this->http->get($url, 3600));
            $user_name = isset($get_user_info_response->current_user) ? $get_user_info_response->current_user->name : $_SESSION['username'];
            $_SESSION['auth_user_name_string'] = $user_name;
        }
        return $_SESSION['auth_user_name_string'];
    }

    private function getCookiesFromHeaders($cookies): array
    {
        $cookieStore = [];

        foreach ($cookies as $cookie) {
            $cookieStore[] = sprintf("%s=%s", $cookie->getName(), $cookie->getValue());
        }

        return $cookieStore;
    }
}
