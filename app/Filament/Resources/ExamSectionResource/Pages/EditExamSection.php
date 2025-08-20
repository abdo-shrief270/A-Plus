<?php

namespace App\Filament\Resources\ExamSectionResource\Pages;

use App\Filament\Resources\ExamSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamSection extends EditRecord
{
    protected static string $resource = ExamSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
