<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
    {
//        dd($data);
        $student=Student::where('user_id',$data['id'])->first();
        if(!$student){
            return $data;
        }
        $data['exam_id']=$student->exam_id;
        $data['exam_date']=$student->exam_date;
        $data['id_number']=$student->id_number;

        return $data;
    }
    protected function afterSave(): void
    {
        $data = $this->form->getState();

        \App\Models\Student::updateOrCreate(
            ['user_id' => $this->record->id], // Match by user_id
            [
                'exam_id'   => $data['exam_id'] ?? null,
                'exam_date' => $data['exam_date'] ?? null,
                'id_number' => $data['id_number'] ?? null,
            ]
        );
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
