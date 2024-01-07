<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Repositories\ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;

class ConfigController extends Controller
{
    protected ConfigRepository $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    /**
     * @OA\Get(
     * path="/api/v1/config",
     * operationId="Config",
     * tags={"Config"},
     * summary="Get Configuration",
     * description="Get Configuration",
     *      @OA\Parameter(
     *         description="The service body ID",
     *         in="query",
     *         name="service_body_id",
     *         required=true,
     *         @OA\Schema(type="number"),
     *      ),
     *      @OA\Parameter(
     *         description="The data type",
     *         in="query",
     *         name="data_type",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="config", value="_YAP_CONFIG_V2_", summary="Configuration"),
     *         @OA\Examples(example="volunteers", value="_YAP_VOLUNTEERS_V2_", summary="Volunteers"),
     *         @OA\Examples(example="groups", value="_YAP_GROUPS_V2_", summary="Groups"),
     *         @OA\Examples(example="callhandling", value="_YAP_CALL_HANDLING_V2_", summary="Call Handling"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Data Returned",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function index(Request $request)
    {
        if ($request->has('parent_id')) {
            $data = $this->config->getDbDataByParentId($request->get('parent_id'), $request->get('data_type'));
        } elseif ($request->get('data_type') === DataType::YAP_GROUPS_V2 && $request->has('id')) {
            $data = $this->config->getDbDataById($request->get('id'), $request->get('data_type'));
        } else {
            $data = $this->config->getDbData($request->get('service_body_id'), $request->get('data_type'));
        }

        if (count($data) > 0) {
            return response()->json([
                'service_body_id'=>$data[0]->service_body_id,
                'id'=>$data[0]->id,
                'parent_id'=>$data[0]->parent_id ?? "null",
                'data'=>json_decode($data[0]->data)
            ])->header("Content-Type", "application/json");
        } else {
              return response()->json(new stdClass())->header("Content-Type", "application/json");
        }
    }

    public function store(Request $request): Response
    {
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

    public function destroy($id): Response
    {
        return response($this->config->deleteDbConfigById($id));
    }
}
