<?php

namespace app\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Repositories\UserRepository;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConfigureVolunteersController extends Controller
{
    protected ConfigData $configData;

    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

    public function index(Request $request): JsonResponse
    {
        $data = ConfigData::getVolunteers($request->get("service_body_id"));

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
        if ($this->authz->canManageUsers()) {
            $data = json_decode($request->getContent());
            $this->user->saveUser($data);
            return response("");
        }

        return response("", 404);
    }

    public function update(Request $request): Response
    {
        $data = json_decode($request->getContent());
        if ($_SESSION['auth_id'] === $data->id) {
            $this->user->editUser($data, 'self');
        } else if ($this->authz->canManageUsers()) {
            $this->user->editUser($data, 'admin');
        } else {
            return response("", 404);
        }

        return response("");
    }

    public function destroy(Request $request): Response
    {
        if ($this->authz->canManageUsers()) {
            $data = json_decode($request->getContent());
            $this->user->deleteUser($data->id);
            return response("");
        }

        return response("", 404);
    }
}
