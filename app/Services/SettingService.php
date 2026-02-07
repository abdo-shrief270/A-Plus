<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingService
{
    /**
     * Get all settings, optionally filtered by group.
     * Only returns non-locked settings for public API.
     *
     * @param string|null $group
     * @param bool $includeAll Include locked settings (for admin)
     * @return Collection
     */
    public function getAllSettings(?string $group = null, bool $includeAll = false): Collection
    {
        $query = Setting::query();

        if ($group) {
            $query->where('group', $group);
        }

        if (!$includeAll) {
            $query->where('is_locked', false);
        }

        return $query->orderBy('group')->orderBy('key')->get();
    }

    /**
     * Get a specific setting by key.
     *
     * @param string $key
     * @return Setting|null
     */
    public function getSettingByKey(string $key): ?Setting
    {
        return Setting::where('key', $key)
            ->where('is_locked', false)
            ->first();
    }

    /**
     * Get all unique groups.
     *
     * @return array
     */
    public function getGroups(): array
    {
        return Setting::where('is_locked', false)
            ->distinct()
            ->pluck('group')
            ->toArray();
    }
}
