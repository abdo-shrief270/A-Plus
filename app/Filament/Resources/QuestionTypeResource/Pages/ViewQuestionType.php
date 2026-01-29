<?php

namespace App\Filament\Resources\QuestionTypeResource\Pages;

use App\Filament\Resources\QuestionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuestionType extends ViewRecord
{
    protected static string $resource = QuestionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
