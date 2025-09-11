<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Hash::make($data['user_name']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
