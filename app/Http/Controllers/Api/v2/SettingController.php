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
     * Get all settings, optionally filtered by group.
     *
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
     * Get a specific setting by key.
     *
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
     * Get all available groups.
     *
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
