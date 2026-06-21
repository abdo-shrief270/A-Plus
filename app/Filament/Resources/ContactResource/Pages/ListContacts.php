<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }

    public function getTabs(): array
    {
        return [
            // Active conversations — anything still in flight, regardless of
            // who last spoke. Excludes closed/resolved tickets.
            'open' => Tab::make('المفتوحة')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', ['closed', 'resolved']))
                ->badge(fn () => Contact::whereNotIn('status', ['closed', 'resolved'])->count())
                ->badgeColor('primary'),

            // We owe a reply: ticket is open AND either has zero staff replies
            // or the most-recent reply on it is from the user.
            'needs_reply' => Tab::make('بحاجة للرد')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => static::scopeNeedsReply($query))
                ->badge(fn () => static::scopeNeedsReply(Contact::query())->count())
                ->badgeColor('warning'),

            // Done — closed or resolved.
            'closed' => Tab::make('المغلقة')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['closed', 'resolved'])),
        ];
    }

    /**
     * Tickets that are waiting on a staff reply: not closed/resolved AND
     * (no replies yet OR the latest reply on the ticket is from a non-staff
     * author). Correlated subquery so each ticket is counted at most once.
     */
    protected static function scopeNeedsReply(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['closed', 'resolved'])
            ->where(function (Builder $q) {
                $q->whereDoesntHave('replies')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('contact_replies as cr')
                            ->whereColumn('cr.contact_id', 'contacts.id')
                            ->where('cr.is_staff', false)
                            ->whereRaw(
                                'cr.id = (SELECT MAX(id) FROM contact_replies WHERE contact_id = contacts.id)'
                            );
                    });
            });
    }

    protected function getHeaderActions(): array
    {
        // No create action — contact messages come from users only.
        return [];
    }
}
