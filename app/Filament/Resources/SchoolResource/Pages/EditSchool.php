<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Backfill a user account if this school predates the login change;
            // otherwise sync name / user_name / password.
            $user = $record->user;
            if (!$user) {
                $user = User::create([
                    'name' => $data['name'] ?? $record->name,
                    'user_name' => $data['user_name'] ?? $record->user_name,
                    'password' => $data['password'] ?? ($data['user_name'] ?? $record->user_name),
                    'type' => 'school',
                ]);
                $record->user_id = $user->id;
            } else {
                $userPayload = [
                    'name' => $data['name'] ?? $user->name,
                    'user_name' => $data['user_name'] ?? $user->user_name,
                ];
                if (filled($data['password'] ?? null)) {
                    $userPayload['password'] = $data['password'];
                }
                $user->update($userPayload);
            }

            // Mirror the password on the schools row when changed.
            if (filled($data['password'] ?? null)) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $record->update($data);
            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
