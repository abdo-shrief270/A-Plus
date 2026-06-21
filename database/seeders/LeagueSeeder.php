<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Student;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    /**
     * Seed the league ladder and backfill students into their league
     * based on current_score. Idempotent: updates by name if rerun.
     */
    public function run(): void
    {
        $leagues = [
            ['name' => 'البرونزية', 'min_score' => 0, 'color' => '#cd7f32', 'order' => 1],
            ['name' => 'الفضية', 'min_score' => 100, 'color' => '#9ca3af', 'order' => 2],
            ['name' => 'الذهبية', 'min_score' => 500, 'color' => '#f59e0b', 'order' => 3],
            ['name' => 'البلاتينية', 'min_score' => 1500, 'color' => '#67e8f9', 'order' => 4],
            ['name' => 'الماسية', 'min_score' => 5000, 'color' => '#a78bfa', 'order' => 5],
        ];

        foreach ($leagues as $league) {
            League::updateOrCreate(['name' => $league['name']], $league);
        }

        // Backfill: place every student in the highest league their score allows.
        Student::query()->chunkById(200, function ($students) {
            foreach ($students as $student) {
                $target = League::where('min_score', '<=', $student->current_score ?? 0)
                    ->orderByDesc('min_score')
                    ->first();
                if ($target && $student->current_league_id !== $target->id) {
                    $student->update(['current_league_id' => $target->id]);
                }
            }
        });
    }
}
