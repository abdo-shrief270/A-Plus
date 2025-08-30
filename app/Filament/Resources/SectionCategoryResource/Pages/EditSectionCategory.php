<?php

namespace App\Filament\Resources\SectionCategoryResource\Pages;

use App\Filament\Resources\SectionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSectionCategory extends EditRecord
{
    protected static string $resource = SectionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
