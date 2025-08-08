<?php

namespace App\Filament\Resources\ParentResource\Pages;

use App\Filament\Resources\ParentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateParent extends CreateRecord
{
    protected static string $resource = ParentResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'parent';
        $data['password'] = $data['user_name'];
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
