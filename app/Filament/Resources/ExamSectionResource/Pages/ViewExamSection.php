<?php

namespace App\Filament\Resources\ExamSectionResource\Pages;

use App\Filament\Resources\ExamSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExamSection extends ViewRecord
{
    protected static string $resource = ExamSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
