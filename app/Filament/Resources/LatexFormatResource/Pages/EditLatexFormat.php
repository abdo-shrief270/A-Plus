<?php

namespace App\Filament\Resources\LatexFormatResource\Pages;

use App\Filament\Resources\LatexFormatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLatexFormat extends EditRecord
{
    protected static string $resource = LatexFormatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
