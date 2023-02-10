<?php

namespace App\Http\Controllers;

use App\Constants\DataType;
use App\Repositories\ConfigRepository;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    protected ConfigRepository $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    public function load(Request $request)
    {
        if ($request->has('parent_id')) {
            $data = $this->config->getDbDataByParentId($request->query('parent_id'), $request->query('data_type'));
        } elseif ($request->query('data_type') === DataType::YAP_GROUPS_V2 && $request->has('id')) {
            $data = $this->config->getDbDataById($request->query('id'), $request->query('data_type'));
        } else {
            $data = $this->config->getDbData($request->query('service_body_id'), $request->query('data_type'));
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

        if ($request->get('data_type') === DataType::YAP_GROUPS_V2 &&
            $request->has('id') && intval($request->get('id')) > 0) {
            $this->config->adminPersistDbConfigById($request->get('id'), $data);
            $request->get('id');
        } else {
            $this->config->adminPersistDbConfig(
                $request->get('service_body_id'),
                $data,
                $request->get('data_type'),
                $request->has('parent_id') ? $request->get('parent_id') : "0"
            );
        }

        return response("");
    }
}
