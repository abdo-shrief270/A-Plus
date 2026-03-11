<?php

namespace App\Filament\Resources\LatexFormatResource\Pages;

use App\Filament\Resources\LatexFormatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLatexFormats extends ListRecords
{
    protected static string $resource = LatexFormatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
