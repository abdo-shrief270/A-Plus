<?php

namespace App\Filament\Resources\PracticeExamResource\Pages;

use App\Filament\Resources\PracticeExamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPracticeExam extends EditRecord
{
    protected static string $resource = PracticeExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
