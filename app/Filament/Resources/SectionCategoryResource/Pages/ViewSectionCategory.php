<?php

namespace App\Filament\Resources\SectionCategoryResource\Pages;

use App\Filament\Resources\SectionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSectionCategory extends ViewRecord
{
    protected static string $resource = SectionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
