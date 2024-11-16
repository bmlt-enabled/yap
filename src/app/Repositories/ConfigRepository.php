<?php
namespace App\Repositories;

use App\Constants\DataType;
use App\Constants\Status;
use Illuminate\Support\Facades\DB;

class ConfigRepository
{
    public function getAllDbData($data_type): array
    {
        return DB::select(
            "SELECT `id`,`data`,`service_body_id`,`parent_id`
                FROM `config` WHERE `data_type`=? AND IFNULL(`status`,0)<>?",
            [$data_type, Status::DELETED]
        );
    }
}
