<?php

namespace App\Notifications;

use App\Models\School;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSchoolRegistrationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public School $school
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->title('مدرسة جديدة')
            ->body("تم إضافة مدرسة جديدة: {$this->school->name}")
            ->icon('heroicon-o-building-office')
            ->iconColor('warning')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('عرض المدرسة')
                    ->url(route('filament.admin.resources.schools.view', ['record' => $this->school->id]))
            ])
            ->getDatabaseMessage();
    }
}
