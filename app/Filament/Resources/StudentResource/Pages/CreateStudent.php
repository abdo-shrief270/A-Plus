<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'student';
        $data['password'] = $data['user_name'];
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        if (!$data['exam_id'])
        {
            return;
        }
        // Create the Student record linked to the new User
        \App\Models\Student::create([
            'user_id'    => $this->record->id,
            'exam_id'    => $data['exam_id'],
            'exam_date'  => $data['exam_date'] ?? null,
            'id_number'  => $data['id_number'] ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
