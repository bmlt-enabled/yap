<?php

namespace App\Models;

use App\Constants\DataType;
use Illuminate\Database\Eloquent\Model;

class ConfigData extends Model
{
    protected $primaryKey = "id";
    protected $table = "config";
    public $timestamps = false;
    protected $fillable = ["service_body_id", "data", "data_type", "parent_id", "status"];

    public static function createCallHandling(
        int $serviceBodyId,
        int $parentServiceBodyId,
        ServiceBodyCallHandling $serviceBodyCallHandlingData
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode([$serviceBodyCallHandlingData]),
            "data_type"=>DataType::YAP_CALL_HANDLING_V2
        ]);
    }

    public static function createServiceBodyConfiguration(
        int $serviceBodyId,
        int $parentServiceBodyId,
        object $serviceBodyConfiguration
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode([$serviceBodyConfiguration]),
            "data_type"=>DataType::YAP_CONFIG_V2
        ]);
    }

    public static function createGroup(
        int $serviceBodyId,
        int $parentServiceBodyId,
        object $serviceBodyConfiguration
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode([$serviceBodyConfiguration]),
            "data_type"=>DataType::YAP_GROUPS_V2
        ]);
    }
}