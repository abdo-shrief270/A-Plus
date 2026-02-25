<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\SettingResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends BaseApiController
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get Settings
     * 
     * Retrieve global platform configuration settings. You can optionally filter
     * these settings by passing a `group` query parameter (e.g., `?group=general`).
     *
     * @unauthenticated false
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');

        $settings = $this->settingService->getAllSettings($group);

        return $this->successResponse(
            ['settings' => SettingResource::collection($settings)],
            'Settings retrieved successfully'
        );
    }

    /**
     * Get Specific Setting
     * 
     * Retrieve a single global setting value by its unique key.
     *
     * @unauthenticated false
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        $setting = $this->settingService->getSettingByKey($key);

        if (!$setting) {
            return $this->errorResponse(
                'Setting not found',
                404
            );
        }

        return $this->successResponse(
            ['setting' => new SettingResource($setting)],
            'Setting retrieved successfully'
        );
    }

    /**
     * Get Setting Groups
     * 
     * Retrieve a list of all available setting group categories used across the platform.
     *
     * @unauthenticated false
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        $groups = $this->settingService->getGroups();

        return $this->successResponse(
            ['groups' => $groups],
            'Setting groups retrieved successfully'
        );
    }
}
