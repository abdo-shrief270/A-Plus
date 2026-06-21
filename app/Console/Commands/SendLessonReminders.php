<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Notifications\SimpleNotification;
use App\Services\StudyPlanService;
use Illuminate\Console\Command;

class SendLessonReminders extends Command
{
    protected $signature = 'reminders:lessons';

    protected $description = 'Notify students about today\'s new lessons and overdue un-opened lessons';

    public function handle(StudyPlanService $service): int
    {
        $sent = 0;

        // Only students with a plan (progress rows) are candidates.
        Student::whereHas('user')
            ->whereHas('lessonProgress')
            ->with('user')
            ->chunkById(200, function ($students) use ($service, &$sent) {
                foreach ($students as $student) {
                    if (!$student->user) {
                        continue;
                    }

                    $counts = $service->reminderCounts($student);
                    if ($counts['overdue'] === 0 && $counts['due_today'] === 0) {
                        continue;
                    }

                    [$title, $description, $color, $icon] = $this->message($counts);

                    $student->user->notify(new SimpleNotification(
                        title: $title,
                        description: $description,
                        link: '/dashboard/plan',
                        color: $color,
                        icon: $icon,
                    ));
                    $sent++;
                }
            });

        $this->info("Lesson reminders sent to {$sent} student(s).");

        return self::SUCCESS;
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function message(array $counts): array
    {
        $overdue = $counts['overdue'];
        $today = $counts['due_today'];

        // Overdue takes priority — it's the nudge that matters most.
        if ($overdue > 0) {
            $parts = ["لديك {$overdue} درس متأخر لم تفتحه بعد"];
            if ($today > 0) {
                $parts[] = "و{$today} درس مستحق اليوم";
            }

            return [
                'دروس متأخرة بانتظارك',
                implode(' ', $parts) . '. لا تتراكم عليك — أكملها الآن.',
                'warning',
                'i-heroicons-exclamation-triangle',
            ];
        }

        // Only today's lessons.
        return [
            'دروس اليوم جاهزة',
            "لديك {$today} درس مجدول لليوم في خطتك الدراسية. ابدأ الآن!",
            'primary',
            'i-heroicons-book-open',
        ];
    }
}
