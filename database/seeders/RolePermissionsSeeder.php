<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Assigns sensible permission sets to the staff roles. super_admin is left
 * untouched (it bypasses every check via Shield's Gate::before). Idempotent:
 * re-running re-syncs each role to exactly the set below. Permissions that
 * don't exist (yet) are silently skipped, so it's safe across schema changes.
 *
 * Run after shield:generate — e.g. `php artisan db:seed --class=RolePermissionsSeeder`.
 */
class RolePermissionsSeeder extends Seeder
{
    /** All CRUD verbs for a resource permission key. */
    private function crud(string $resource): array
    {
        return array_map(fn ($v) => "{$v}_{$resource}", [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
        ]);
    }

    /** Read-only verbs for a resource permission key. */
    private function readOnly(string $resource): array
    {
        return ["view_{$resource}", "view_any_{$resource}"];
    }

    public function run(): void
    {
        $contentResources = [
            'question', 'question::type', 'exam', 'exam::section', 'section::category',
            'lesson', 'article', 'course', 'practice::exam', 'latex::format', 'league',
        ];

        $commerceResources = ['payment', 'plan', 'coupon', 'enrollment', 'contact'];

        // المدير (Manager): everything except role management and the env viewer.
        $managerExclude = ['view_role', 'view_any_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role', 'page_ViewEnv'];
        $managerPerms = Permission::pluck('name')
            ->reject(fn ($p) => in_array($p, $managerExclude, true))
            ->values()->all();

        $map = [
            // Manager — near-super-admin, minus role admin + env access.
            'المدير' => $managerPerms,
            // مدير النظام — same broad operational scope as Manager.
            'مدير النظام' => $managerPerms,

            // مبيعات (Sales) — commerce + read access to people, plus revenue widgets.
            'مبيعات' => array_merge(
                array_merge(...array_map(fn ($r) => $this->crud($r), $commerceResources)),
                $this->readOnly('student'),
                $this->readOnly('school'),
                $this->readOnly('parent'),
                ['widget_StatsOverview', 'widget_EconomyStatsWidget', 'widget_RevenueStatsWidget', 'widget_PendingActionsWidget'],
            ),

            // مدخل بيانات (Data entry) — content authoring only, read students.
            'مدخل بيانات' => array_merge(
                array_merge(...array_map(fn ($r) => $this->crud($r), $contentResources)),
                $this->readOnly('student'),
                ['widget_StatsOverview', 'widget_QuestionDifficultyChart', 'widget_LessonPageTypeChart'],
            ),
        ];

        $existing = Permission::pluck('name')->flip();

        foreach ($map as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->command?->warn("Role '{$roleName}' not found — skipped.");
                continue;
            }
            // Keep only permissions that actually exist.
            $valid = collect($perms)->unique()->filter(fn ($p) => $existing->has($p))->values()->all();
            $role->syncPermissions($valid);
            $this->command?->info("{$roleName}: " . count($valid) . ' permissions');
        }
    }
}
