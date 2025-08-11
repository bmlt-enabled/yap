<?php

namespace App\Models;

use App\Constants\DataType;
use App\Constants\Status;
use App\Structures\Group;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\Settings;
use App\Structures\VolunteerData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use stdClass;

class ConfigData extends Model
{
    protected $primaryKey = "id";
    protected $table = "config";
    public $timestamps = false;
    protected $fillable = ["service_body_id", "data", "data_type", "parent_id", "status"];

    public static function createServiceBodyCallHandling(
        int $serviceBodyId,
        ServiceBodyCallHandling $serviceBodyCallHandlingData
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>0,
            "data"=>json_encode([$serviceBodyCallHandlingData]),
            "data_type"=>DataType::YAP_CALL_HANDLING_V2
        ]);
    }

    public static function createServiceBodyConfiguration(
        int $serviceBodyId,
        Settings $serviceBodyConfiguration
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>0,
            "data"=>json_encode([$serviceBodyConfiguration]),
            "data_type"=>DataType::YAP_CONFIG_V2
        ]);
    }

    public static function updateServiceBodyConfiguration(
        int $serviceBodyId,
        Settings $serviceBodyConfiguration
    ) : void {
        self::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->update(['data' => json_encode([$serviceBodyConfiguration])]);
    }

    public static function getServiceBodyConfiguration(int $serviceBodyId): Collection
    {
        return ConfigData::select(['data', 'service_body_id', 'id', 'parent_id'])
            ->where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function getAllServiceBodyConfiguration(): Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function updateServiceBodyCallHandling(
        int $serviceBodyId,
        ServiceBodyCallHandling $volunteerDataArray
    ) : void {
        self::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CALL_HANDLING_V2)
            ->update(['data' => json_encode([$volunteerDataArray])]);
    }

    public static function createGroup(
        int $serviceBodyId,
        Group $serviceBodyConfiguration
    ) : int {
        return self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>null,
            "data"=>json_encode([$serviceBodyConfiguration]),
            "data_type"=>DataType::YAP_GROUPS_V2
        ])->id;
    }

    public static function addGroupToVolunteers(
        int $serviceBodyId,
        int $parentServiceBodyId,
        object $groupConfiguration
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$parentServiceBodyId,
            "data"=>json_encode([$groupConfiguration]),
            "data_type"=>DataType::YAP_VOLUNTEERS_V2
        ]);
    }

    public static function createGroupVolunteers(
        int $serviceBodyId,
        int $groupId,
        array $volunteerDataArray
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>$groupId,
            "data"=>json_encode($volunteerDataArray),
            "data_type"=>DataType::YAP_GROUP_VOLUNTEERS_V2
        ]);
    }

    public static function updateGroupVolunteers(
        int $groupId,
        array $volunteerDataArray
    ) : void {
        self::where('parent_id', $groupId)
            ->where('data_type', DataType::YAP_GROUP_VOLUNTEERS_V2)
            ->update(['data' => json_encode($volunteerDataArray)]);
    }

    public static function getGroupVolunteers(
        int $groupId
    ) : Collection {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('parent_id', $groupId)
            ->where('data_type', DataType::YAP_GROUP_VOLUNTEERS_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
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
        $volunteers = ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();

        foreach ($volunteers as $volunteer) {
            $decodedData = json_decode($volunteer->data);
            if (is_array($decodedData) && count($decodedData) > 0) {
                // Decode the shift schedule for each volunteer in the array
                foreach ($decodedData as $volunteerData) {
                    if (isset($volunteerData->volunteer_shift_schedule)) {
                        $volunteerData->volunteer_shift_schedule = json_decode(base64_decode($volunteerData->volunteer_shift_schedule));
                    }
                }
            }
            $volunteer->data = $decodedData;
        }

        return $volunteers;
    }

    public static function getVolunteersRecursively(array $serviceBodyIds) : Collection
    {
        $volunteers = ConfigData::select(['data','service_body_id','id','parent_id'])
            ->whereIn('service_body_id', $serviceBodyIds)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();

        foreach ($volunteers as $volunteer) {
            $decodedData = json_decode($volunteer->data);
            if (is_array($decodedData) && count($decodedData) > 0) {
                // Decode the shift schedule for each volunteer in the array
                foreach ($decodedData as $volunteerData) {
                    if (isset($volunteerData->volunteer_shift_schedule)) {
                        $volunteerData->volunteer_shift_schedule = json_decode(base64_decode($volunteerData->volunteer_shift_schedule));
                    }
                }
            }
            $volunteer->data = $decodedData;
        }

        return $volunteers;
    }

    public static function getCallHandling(int $serviceBodyId): Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CALL_HANDLING_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function getAllCallHandling(): Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('data_type', DataType::YAP_CALL_HANDLING_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function createVolunteers(
        int $serviceBodyId,
        array $volunteerDataArray
    ) : void {
        self::create([
            "service_body_id"=>$serviceBodyId,
            "parent_id"=>0,
            "data"=>json_encode($volunteerDataArray),
            "data_type"=>DataType::YAP_VOLUNTEERS_V2
        ]);
    }

    public static function updateVolunteers(
        int $serviceBodyId,
        array $volunteerDataArray
    ) : void {
        self::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->update(['data' => json_encode($volunteerDataArray)]);
    }

    public static function getAllGroups(): Collection
    {
        return ConfigData::select(['data','service_body_id','id','parent_id'])
            ->where('data_type', DataType::YAP_GROUPS_V2)
            ->whereRaw('IFNULL(`status`, 0) <> ?', [Status::DELETED])
            ->get();
    }

    public static function deleteGroup($id) : int
    {
        $group = self::select(['id'])
            ->where('id', $id)
            ->where('data_type', DataType::YAP_GROUPS_V2);

        if ($group) {
            return $group->delete();
        }

        return false;
    }

    public static function updateGroup($id, Group $groupData) : ConfigData
    {
        self::where('id', $id)
            ->where('data_type', DataType::YAP_GROUPS_V2)
            ->update(['data' => json_encode([$groupData])]);

        return self::select(['service_body_id'])
            ->where('id', $id)
            ->where('data_type', DataType::YAP_GROUPS_V2)
            ->first();
    }
}
