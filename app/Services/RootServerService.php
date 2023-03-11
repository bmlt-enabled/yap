<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Constants\CacheType;
use App\Utility\Sort;
use CurlException;

class RootServerService
{
    protected SettingsService $settings;
    protected $serviceBodies;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService;
        $bmlt_search_endpoint = sprintf(
            '%s/client_interface/json/?switcher=GetServiceBodies',
            $this->settings->getAdminBMLTRootServer()
        );
        $this->serviceBodies = json_decode($this->get($bmlt_search_endpoint, false, 3600));
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
                array_push($service_bodies, (object)[
                    "id" => "0"
                ]);
            }
        } else {
            $service_bodies = [];
        }

        Sort::sortOnField($service_bodies, 'name');
        return $service_bodies;
    }

    public function getServiceBodiesRights()
    {
        if (isset($_SESSION['auth_mechanism'])) {
            if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
                $url = sprintf(
                    '%s/local_server/server_admin/json.php?admin_action=get_permissions',
                    $this->settings->getAdminBMLTRootServer()
                );
                $service_bodies_for_user = json_decode($this->get($url, true));

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

                $service_bodies = $this->serviceBodies;
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
                return $this->serviceBodies;
            } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2) {
                $service_bodies = $this->serviceBodies;
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

    public function getServiceBody($service_body_id)
    {
        foreach ($this->serviceBodies as $service_body) {
            if ($service_body->id == $service_body_id) {
                return $service_body;
            }
        }

        return null;
    }

    private function get($url, $bmltAuth = false, $cache_expiry = 0, $cache_type = CacheType::DATABASE)
    {
        return file_get_contents($url);

        $data = null;
        // $data = $cache_expiry > 0 ? getCache($url) : null;
        if ($data == null) {
            // log_debug($url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
//            if ($bmltAuth) {
//                curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
//            }
            $data = curl_exec($ch);
            $errorno = curl_errno($ch);
            curl_close($ch);
            if ($errorno > 0) {
                throw new CurlException(curl_strerror($errorno));
            } elseif ($cache_expiry > 0) {
                setCache($url, $data, $cache_expiry, $cache_type);
            }
        }

        return $data;
    }
}
