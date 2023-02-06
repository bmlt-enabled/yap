<?php

namespace App\Http\Controllers;

use App\Constants\DataType;
use App\Queries\ConfigQueries;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function load(Request $request)
    {
        if (isset($parent_id) || $request->has('parent_id')) {
            $data = ConfigQueries::getDbDataByParentId(
                $parent_id ?? $request->has('parent_id'),
                $request->query('data_type')
            );
        } elseif ($_REQUEST['data_type'] === DataType::YAP_GROUPS_V2 && isset($id)) {
            $data = ConfigQueries::getDbDataById($id, $request->query('data_type'));
        } else {
            $data = ConfigQueries::getDbData($request->query('service_body_id'), $request->query('data_type'));
        }

        if (count($data) > 0) {
            return response()->json([
                'service_body_id'=>$data[0]->service_body_id,
                'id'=>$data[0]->id,
                'parent_id'=>$data[0]->parent_id ?? "null",
                'data'=>json_decode($data[0]->data)
            ])->header("Content-Type", "application/json");
        } else {
            return response()->json()->header("Content-Type", "application/json");
        }
    }

    public function save(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $data = $request->getContent();

        if ($request->query('data_type') === DataType::YAP_GROUPS_V2 &&
            $request->has('id') && intval($request->query('id')) > 0) {
            ConfigQueries::admin_PersistDbConfigById($request->query('id'), $data);
            $request->query('id');
        } else {
            ConfigQueries::admin_PersistDbConfig(
                $request->query('service_body_id'),
                $data,
                $request->query('data_type'),
                $request->has('parent_id') ? $request->query('parent_id') : "0"
            );
        }

        return $this->load($request);
    }
}
