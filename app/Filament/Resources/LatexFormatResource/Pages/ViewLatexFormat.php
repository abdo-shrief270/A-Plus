<?php

namespace App\Filament\Resources\LatexFormatResource\Pages;

use App\Filament\Resources\LatexFormatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLatexFormat extends ViewRecord
{
    protected static string $resource = LatexFormatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
