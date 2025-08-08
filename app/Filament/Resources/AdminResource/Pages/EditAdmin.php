<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Models\Admin;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles']=Admin::find($data['id'])->roles?->pluck('name');
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['password']);
        return $data;
    }
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        foreach ($record->roles->pluck('name') as $role)
        {
            $record->removeRole($role);
        }
        $record->assignRole($data['roles']);
        return $record;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
