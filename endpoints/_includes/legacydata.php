<?php
$flag_setting = getFlag('root_server_data_migration');

function getHelplineData($service_body_id, $data_type)
{
    $helpline_data_items = [];
    auth_v1($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password'], true);
    $helpline_data = json_decode(get(getAdminBMLTRootServer()
        . "/client_interface/json/?switcher=GetSearchResults"
        . (($service_body_id != 0) ? "&services=" . $service_body_id : "")
        . "&meeting_key=meeting_name&meeting_key_value=" . $data_type
        . "&advanced_published=0"));

    if ($helpline_data != null) {
        foreach ($helpline_data as $item) {
            $json_string = str_replace(';', ',', html_entity_decode(explode('#@-@#', $item->contact_phone_1)[2]));
            array_push($helpline_data_items, [
                'id'              => intval($item->id_bigint),
                'data'            => json_decode($json_string)->data,
                'service_body_id' => intval($item->service_body_bigint)
            ]);
        }
    }

    return $helpline_data_items;
}

if (intval($flag_setting) !== 1) {
    if (setting("auth_bmlt")) {
        $v1Config = getHelplineData(0, DataType::YAP_CONFIG);

        foreach ($v1Config as $item) {
            admin_PersistDbConfig($item['service_body_id'], json_encode($item['data']), DataType::YAP_CALL_HANDLING_V2);
        }

        $v1Volunteers = getHelplineData(0, DataType::YAP_DATA);

        foreach ($v1Volunteers as $volunteer) {
            admin_PersistDbConfig($volunteer['service_body_id'], json_encode($volunteer['data']), DataType::YAP_VOLUNTEERS_V2);
        }

        setFlag('root_server_data_migration', 1);
    } else {
        echo "Cannot migrate v2 to v3 data because bmlt_auth is disabled.  Must be enabled so the migration can occur, it can be disabled afterwards.";
    }
}
