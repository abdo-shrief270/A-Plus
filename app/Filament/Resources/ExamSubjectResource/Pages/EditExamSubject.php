<?php

namespace App\Filament\Resources\ExamSubjectResource\Pages;

use App\Filament\Resources\ExamSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamSubject extends EditRecord
{
    protected static string $resource = ExamSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
