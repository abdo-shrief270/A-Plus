<?php

namespace App\Filament\Resources\ExamSubjectResource\Pages;

use App\Filament\Resources\ExamSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExamSubject extends ViewRecord
{
    protected static string $resource = ExamSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
