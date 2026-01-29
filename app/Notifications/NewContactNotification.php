<?php

namespace App\Notifications;

use App\Models\Contact;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewContactNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contact $contact
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->title('رسالة جديدة')
            ->body("رسالة من {$this->contact->name}")
            ->icon('heroicon-o-envelope')
            ->iconColor('info')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('عرض الرسالة')
                    ->url(fn() => \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $this->contact->id]))
            ])
            ->getDatabaseMessage();
    }
}
