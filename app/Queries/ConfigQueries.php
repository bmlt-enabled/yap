<?php
namespace App\Queries;

use App\Constants\DataType;
use Illuminate\Support\Facades\DB;

class ConfigQueries
{
    public static function getDbDataByParentId($parent_id, $data_type)
    {
        return DB::select(
            'SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `parent_id`= ? AND `data_type`= ?',
            [$parent_id, $data_type]
        );
    }

    public static function getDbDataById($id, $data_type)
    {
        return DB::select(
            'SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `id`=? AND `data_type`=?',
            [$id, $data_type]
        );
    }

    public static function getDbData($service_body_id, $data_type)
    {
        return DB::select(
            "SELECT `data`,`service_body_id`,`id`,`parent_id` FROM `config` WHERE `service_body_id`=? AND `data_type`=?",
            [$service_body_id, $data_type]
        );
    }

    public static function admin_PersistDbConfigById($id, $data)
    {
        return DB::update("UPDATE `config` SET `data`=? WHERE `id`=?", [
            $data, $id
        ]);
    }

    public static function admin_PersistDbConfig($service_body_id, $data, $data_type, $parent_id = 0)
    {
        $current_data_check = (isset($parent_id) && $parent_id > 0
            ? ConfigQueries::getDbDataByParentId($parent_id, $data_type)
            : ConfigQueries::getDbData($service_body_id, $data_type));

        if (count($current_data_check) == 0 || $data_type == DataType::YAP_GROUPS_V2) {
            $parent_id = $parent_id == 0 ? null : $parent_id;
            DB::insert(
                "INSERT INTO `config` (`service_body_id`,`data`,`data_type`,`parent_id`) VALUES (?,?,?,?)",
                [$service_body_id,$data, $data_type, $parent_id]
            );
            $result = DB::select(
                "SELECT MAX(id) as id FROM `config` WHERE `service_body_id`=? AND `data_type`=?",
                [$service_body_id, $data_type]
            );
            return $result[0]['id'];
        } else {
            if (isset($parent_id) && $parent_id > 0) {
                DB::insert(
                    "UPDATE `config` SET `data`=? WHERE `service_body_id`=? AND `data_type`=? AND `parent_id`=?",
                    [
                    $data,
                    $service_body_id,
                    $data_type,
                    $parent_id
                    ]
                );
            } else {
                DB::insert(
                    "UPDATE `config` SET `data`=? WHERE `service_body_id`=? AND `data_type`=?",
                    [
                        $data,
                        $service_body_id,
                        $data_type
                    ]
                );
            }
        }
    }
}
