<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('معاينة الدرس')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading(fn() => "معاينة: {$this->record->title}")
                ->modalContent(fn() => view('filament.resources.lesson-resource.preview', ['lesson' => $this->record]))
                ->modalWidth('7xl')
                ->slideOver()
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق'),
            Actions\DeleteAction::make(),
        ];
    }
}
