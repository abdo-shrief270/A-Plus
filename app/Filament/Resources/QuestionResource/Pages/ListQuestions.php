<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Models\Question;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuestionResource\Widgets\QuestionStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),

            'text' => Tab::make('نصي')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('type', fn ($q) => $q->where('name', 'نصي'))),

            'image' => Tab::make('صوري')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('type', fn ($q) => $q->where('name', 'صوري'))),

            'trending' => Tab::make('Trending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_new', true)->whereNull('practice_exam_id')),

            'linked_to_model' => Tab::make('مرتبط بنموذج')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('practice_exam_id')),

            'comparison' => Tab::make('مقارنة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('type', fn ($q) => $q->where('name', 'مقارنة'))),

            'needs_review' => Tab::make('يحتاج مراجعة')
                ->badge(fn () => Question::whereDoesntHave('answers', fn ($q) => $q->where('is_correct', true))->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('answers', fn ($q) => $q->where('is_correct', true))),
        ];
    }
}
