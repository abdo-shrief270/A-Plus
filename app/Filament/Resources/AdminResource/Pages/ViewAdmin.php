<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Models\Admin;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdmin extends ViewRecord
{
    protected static string $resource = AdminResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles']=Admin::find($data['id'])->roles?->pluck('name');
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
