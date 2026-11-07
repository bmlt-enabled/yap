<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use App\Services\SettingsService;
use App\Models\ConfigData;
use App\Constants\DataType;
use App\Structures\Settings;
use App\Utilities\VolunteerScheduleHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Structures\Localizations;

/**
 * @OA\Tag(
 *     name="Settings",
 *     description="System settings management endpoints"
 * )
 */
class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected AuthorizationService $authz;

    public function __construct(SettingsService $settingsService, AuthorizationService $authz)
    {
        $this->settingsService = $settingsService;
        $this->authz = $authz;
    }

    /**
     * The service body in the route, or the error response to return when it
     * is malformed or belongs to someone else. Per-service-body settings drive
     * that service body's call routing, so only its admins (or a top-level
     * admin) may read or change them - the same rule as call handling.
     */
    private function authorizedServiceBodyId(Request $request): int|JsonResponse
    {
        $serviceBodyId = (string)$request->route('serviceBodyId');
        if (!ctype_digit($serviceBodyId) || intval($serviceBodyId) <= 0) {
            return response()->json(['message' => 'Invalid service body ID'], 400);
        }

        $serviceBodyId = intval($serviceBodyId);
        if (!$this->authz->canAccessServiceBody($serviceBodyId)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        return $serviceBodyId;
    }

    /**
     * The submitted fields as setting => value, or a list of what is wrong with
     * them. Only allowlisted settings are accepted: SessionService ignores
     * anything else when it applies the overrides to a call, so storing it
     * would only look like it worked.
     *
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function validatedFields(mixed $decodedData): array
    {
        if (!is_object($decodedData) || !isset($decodedData->fields) || !is_array($decodedData->fields)) {
            return [[], ['Expected a JSON object with a "fields" array.']];
        }

        $allowlist = $this->settingsService->allowlist();
        $fields = [];
        $errors = [];
        foreach ($decodedData->fields as $index => $field) {
            if (!is_object($field) || !isset($field->setting) || !is_string($field->setting)
                || !property_exists($field, 'value')) {
                $errors[] = "fields[$index] must have a string \"setting\" and a \"value\".";
            } elseif (!array_key_exists($field->setting, $allowlist)) {
                $errors[] = "fields[$index]: \"{$field->setting}\" is not a setting.";
            } elseif (!is_scalar($field->value) && !is_null($field->value)) {
                $errors[] = "fields[$index]: the value of \"{$field->setting}\" must be a string, number, boolean or null.";
            } else {
                $fields[$field->setting] = $field->value;
            }
        }

        return [$fields, $errors];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/allowlist",
     *     tags={"Settings"},
     *     summary="Get allowlist settings",
     *     @OA\Response(
     *         response=200,
     *         description="Allowlist settings retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="setting", type="string"),
     *                 @OA\Property(property="default", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function allowlist()
    {
        $allowlist = [];
        foreach ($this->settingsService->allowlist() as $key => $value) {
            if (!$value['hidden']) {
                $allowlist[] = [
                    "setting" => $key,
                    "default" => $value['default'],
                    "description" => !empty($value['description']) ? sprintf("%s%s", $this->settingsService->get("docs_base"), $value['description']) : ""
                ];
            }
        }
        return response()->json($allowlist);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/settings/serviceBody/{serviceBodyId}",
     *     tags={"Settings"},
     *     summary="Store or update service body configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="path",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="setting", type="string"),
     *                     @OA\Property(property="value", type="string"),
     *                     @OA\Property(property="default", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service body configuration saved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="languageSelections",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="default", type="string"),
     *                     @OA\Property(property="docs", type="string"),
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="value", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="The service body ID is not a positive integer"),
     *     @OA\Response(response=403, description="The user does not administer this service body"),
     *     @OA\Response(response=422, description="The body is not a fields array of allowlisted settings")
     * )
     */
    public function saveServiceBodyConfiguration(Request $request)
    {
        $serviceBodyId = $this->authorizedServiceBodyId($request);
        if ($serviceBodyId instanceof JsonResponse) {
            return $serviceBodyId;
        }

        [$fields, $errors] = $this->validatedFields(json_decode($request->getContent()));
        if (!empty($errors)) {
            return response()->json(['message' => 'Invalid settings', 'errors' => $errors], 422);
        }

        // Convert array of fields to flat object structure
        $config = new Settings((object)$fields);

        $existingRecord = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->first();

        if ($existingRecord) {
            // If the record exists, update it
            ConfigData::updateServiceBodyConfiguration($serviceBodyId, $config);
        } else {
            // Otherwise, create a new record
            ConfigData::createServiceBodyConfiguration($serviceBodyId, $config);
        }

        return self::index($request);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings",
     *     tags={"Settings"},
     *     summary="Get system settings",
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="languageSelections",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="default", type="string"),
     *                     @OA\Property(property="docs", type="string"),
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="value", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $settings = [];

        foreach ($this->settingsService->allowlist() as $key => $value) {
            if (!$value['hidden']) {
                $settings[] = [
                    "default" => $value['default'],
                    "docs" => !empty($value['description']) ? sprintf("%s%s", $this->settingsService->get("docs_base"), $value['description']) : "",
                    "key" => $key,
                    "source" => $this->settingsService->source($key),
                    "value" => $this->settingsService->get($key),
                ];
            }
        }

        return response()->json([
            'languageSelections'=>$this->settingsService->languageSelections(),
            'settings'=>$settings,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/serviceBody/{serviceBodyId}",
     *     tags={"Settings"},
     *     summary="Get service body configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="path",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service body configuration retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="setting", type="string"),
     *                 @OA\Property(property="value", type="string"),
     *                 @OA\Property(property="default", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="The service body ID is not a positive integer"),
     *     @OA\Response(response=403, description="The user does not administer this service body")
     * )
     */
    public function getServiceBodyConfiguration(Request $request)
    {
        $serviceBodyId = $this->authorizedServiceBodyId($request);
        if ($serviceBodyId instanceof JsonResponse) {
            return $serviceBodyId;
        }

        $config = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->first();

        if (!$config) {
            return response()->json([]);
        }

        $decodedData = json_decode($config->data);
        if (!isset($decodedData)) {
            return response()->json([]);
        }

        // Handle both array and direct object structures
        $configData = is_array($decodedData) ? $decodedData[0] : $decodedData;

        // Convert object properties to array of setting/value pairs
        $fields = array_map(
            fn($key, $value) => ['setting' => $key, 'value' => $value],
            array_keys((array)$configData),
            array_values((array)$configData)
        );

        return response()->json($fields);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/localizations",
     *     tags={"Settings"},
     *     summary="Get localization words for the current session language",
     *     @OA\Response(
     *         response=200,
     *         description="Localization words retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             description="Localization words for the current session language"
     *         )
     *     )
     * )
     */
    public function getLocalizations(Request $request)
    {
        $localizations = new Localizations();

        // Check for language query parameter first, then fall back to session
        $currentLanguage = $request->query('language') ?? $this->settingsService->getWordLanguage();

        // Validate language is available
        $availableLanguages = $this->settingsService->availableLanguages();
        if (!array_key_exists($currentLanguage, $availableLanguages)) {
            $currentLanguage = 'en-US';
        }

        // Get the localization data for the current language
        $localizationData = $localizations->getLocalization($currentLanguage);

        // Convert to array if it's an object
        if (is_object($localizationData)) {
            $localizationData = (array) $localizationData;
        }

        // Sort the localization data alphabetically by keys
        ksort($localizationData);

        return response()->json($localizationData);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/timezones",
     *     tags={"Settings"},
     *     summary="Get list of all available timezones",
     *     @OA\Response(
     *         response=200,
     *         description="Timezone list retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string")
     *         )
     *     )
     * )
     */
    public function getTimezones()
    {
        $timezones = VolunteerScheduleHelpers::getTimezoneList();
        return response()->json($timezones);
    }
}
