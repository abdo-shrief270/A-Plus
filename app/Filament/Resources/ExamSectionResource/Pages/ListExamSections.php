<?php

namespace App\Filament\Resources\ExamSectionResource\Pages;

use App\Filament\Resources\ExamSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamSections extends ListRecords
{
    protected static string $resource = ExamSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExamSectionResource\Widgets\ExamSectionStatsOverview::class,
        ];
    }
}
