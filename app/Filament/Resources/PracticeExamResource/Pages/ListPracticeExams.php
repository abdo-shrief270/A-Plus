<?php

namespace App\Filament\Resources\PracticeExamResource\Pages;

use App\Filament\Resources\PracticeExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPracticeExams extends ListRecords
{
    protected static string $resource = PracticeExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
