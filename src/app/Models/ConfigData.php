<?php

namespace App\Models;

use App\Constants\DataType;
use App\Constants\Status;
use Illuminate\Database\Eloquent\Collection;
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

    public static function createVolunteer(
        int $serviceBodyId,
        int $parentServiceBodyId,
        VolunteerData $volunteerConfiguration
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode([$volunteerConfiguration]),
            "data_type"=>DataType::YAP_VOLUNTEERS_V2
        ]);
    }

    public static function getVolunteers(int $serviceBodyId) : Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function getCallHandling(int $serviceBodyId): Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CALL_HANDLING_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function createVolunteers(
        int $serviceBodyId,
        int $parentServiceBodyId,
        array $volunteerDataArray
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode($volunteerDataArray),
            "data_type"=>DataType::YAP_VOLUNTEERS_V2
        ]);
    }
}
