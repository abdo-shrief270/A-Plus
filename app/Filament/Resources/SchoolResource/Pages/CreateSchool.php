<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use App\Models\School;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // The user that logs in. Hashed automatically by the User cast.
            $user = User::create([
                'name' => $data['name'],
                'user_name' => $data['user_name'],
                'password' => $data['password'],
                'type' => 'school',
            ]);

            // School metadata, linked to the user.
            return School::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'user_name' => $data['user_name'],
                'password' => Hash::make($data['password']),
                'active' => true,
            ]);
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
