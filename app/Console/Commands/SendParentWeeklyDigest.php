<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SimpleNotification;
use App\Services\ParentWeeklyDigestService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendParentWeeklyDigest extends Command
{
    protected $signature = 'digest:parent-weekly';

    protected $description = 'Send each parent an in-app weekly summary of their children\'s study activity';

    public function handle(ParentWeeklyDigestService $service): int
    {
        $since = Carbon::now()->subWeek();
        $sent = 0;

        User::isParent()->with('studentParent.student.user')->chunkById(100, function ($parents) use ($service, $since, &$sent) {
            foreach ($parents as $parent) {
                $summaries = $service->summariesForParent($parent, $since);
                if (empty($summaries)) {
                    continue;
                }

                $lines = array_map(fn ($s) => $service->childHeadline($s), $summaries);
                $anyActive = collect($summaries)->contains('was_active', true);

                $parent->notify(new SimpleNotification(
                    title: 'ملخص الأسبوع لأبنائك',
                    description: implode("\n", $lines),
                    link: '/dashboard',
                    color: $anyActive ? 'success' : 'warning',
                    icon: 'i-heroicons-chart-bar',
                ));
                $sent++;
            }
        });

        $this->info("Parent weekly digest sent to {$sent} parent(s).");

        return self::SUCCESS;
    }
}
