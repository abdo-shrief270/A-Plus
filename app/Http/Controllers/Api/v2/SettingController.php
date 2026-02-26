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
     * Get Settings (إعدادات المنصة)
     * 
     * يجلب إعدادات التكوين العامة للمنصة. 
     * يمكن للواجهة الأمامية استخدام هذا المسار لجلب نصوص الشروط والأحكام، روابط مواقع التواصل، وغيرها.
     * يمكن الاستعلام عن مجموعة محددة بتمرير `group` كمعامل استعلام.
     *
     * @queryParam group string optional التصنيف/المجموعة المُراد جلب إعداداتها فقط (مثال: general). Example: general
     *
     * @group Support & Settings (الإعدادات والدعم)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{settings: array}}
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
     * Get Specific Setting (جلب إعداد بعينه)
     * 
     * يجلب قيمة محددة مفردة بناءً على المفتاح (Key) الفريد الخاص بها.
     *
     * @pathParam key string required مفتاح الإعداد المُراد جلبه. Example: site_name
     *
     * @group Support & Settings (الإعدادات والدعم)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{setting: array}}
     * @response 404 array{status: int, message: string}
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
     * Get Setting Groups (مجموعات الإعدادات)
     * 
     * يجلب قائمة بجميع أنواع مجموعات الإعدادات المتوفرة في النظام 
     * (مثل: general, social_links, terms).
     *
     * @group Support & Settings (الإعدادات والدعم)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{groups: array}}
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
