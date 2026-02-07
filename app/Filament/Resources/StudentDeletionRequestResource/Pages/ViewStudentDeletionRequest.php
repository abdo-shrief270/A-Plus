<?php

namespace App\Filament\Resources\StudentDeletionRequestResource\Pages;

use App\Filament\Resources\StudentDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentDeletionRequest extends ViewRecord
{
    protected static string $resource = StudentDeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
