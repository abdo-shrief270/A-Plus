<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Subscription plans (recurring access, with monthly points allowance)
            [
                'name' => 'الباقة الشهرية الأساسية',
                'type' => 'subscription',
                'price' => 99,
                'points' => 500,
                'duration_days' => 30,
            ],
            [
                'name' => 'الباقة الشهرية المميزة',
                'type' => 'subscription',
                'price' => 199,
                'points' => 1500,
                'duration_days' => 30,
            ],
            [
                'name' => 'الباقة الفصلية',
                'type' => 'subscription',
                'price' => 499,
                'points' => 5000,
                'duration_days' => 90,
            ],
            [
                'name' => 'الباقة السنوية',
                'type' => 'subscription',
                'price' => 1499,
                'points' => 20000,
                'duration_days' => 365,
            ],
            // Pack (one-time points top-up)
            [
                'name' => 'حزمة 100 نقطة',
                'type' => 'pack',
                'price' => 25,
                'points' => 100,
                'duration_days' => null,
            ],
            [
                'name' => 'حزمة 500 نقطة',
                'type' => 'pack',
                'price' => 100,
                'points' => 500,
                'duration_days' => null,
            ],
            [
                'name' => 'حزمة 2000 نقطة',
                'type' => 'pack',
                'price' => 350,
                'points' => 2000,
                'duration_days' => null,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command?->info('Seeded ' . count($plans) . ' plans.');
    }
}
