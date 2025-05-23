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
        if(!isset($data['password']))
        {
            $data['password'] = Hash::make('12345678');
        }else{
            $data['password'] = Hash::make($data['password']);
        }
        return $data;
    }
}
