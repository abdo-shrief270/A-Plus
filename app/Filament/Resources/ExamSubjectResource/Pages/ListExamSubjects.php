<?php

namespace App\Filament\Resources\ExamSubjectResource\Pages;

use App\Filament\Resources\ExamSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamSubjects extends ListRecords
{
    protected static string $resource = ExamSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
