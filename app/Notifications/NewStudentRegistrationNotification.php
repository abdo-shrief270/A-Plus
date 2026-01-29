<?php

namespace App\Notifications;

use App\Models\Student;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStudentRegistrationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Student $student
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $userName = $this->student->user->name ?? 'غير معروف';
        $examName = $this->student->exam->name ?? 'غير محدد';

        return FilamentNotification::make()
            ->title('طالب جديد')
            ->body("تم تسجيل الطالب {$userName} للامتحان: {$examName}")
            ->icon('heroicon-o-user-plus')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('عرض الطالب')
                    ->url(route('filament.admin.resources.students.view', ['record' => $this->student->id]))
            ])
            ->getDatabaseMessage();
    }
}
