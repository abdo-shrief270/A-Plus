<?php

namespace App\Filament\Resources\SectionCategoryResource\Pages;

use App\Filament\Resources\SectionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSectionCategories extends ListRecords
{
    protected static string $resource = SectionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
