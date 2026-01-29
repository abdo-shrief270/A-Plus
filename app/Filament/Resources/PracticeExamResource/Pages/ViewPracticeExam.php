<?php

namespace App\Filament\Resources\PracticeExamResource\Pages;

use App\Filament\Resources\PracticeExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPracticeExam extends ViewRecord
{
    protected static string $resource = PracticeExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
