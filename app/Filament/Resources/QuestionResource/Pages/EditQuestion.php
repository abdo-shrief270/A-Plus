<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $formData = $this->data;
        $assignTo = $formData['assign_to'] ?? 'category';

        if ($assignTo === 'category') {
            $this->record->categories()->sync($formData['category_ids'] ?? []);
            $this->record->articles()->detach();
        }

        if ($assignTo === 'article') {
            $this->record->articles()->sync($formData['article_ids'] ?? []);
            $this->record->categories()->detach();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
