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
        $data = $this->form->getState();

        if (($data['assign_to'] ?? 'category') === 'category') {
            $this->record->categories()->sync($data['category_ids'] ?? []);
            $this->record->articles()->detach(); // Clear articles if switching to category
        }

        if (($data['assign_to'] ?? '') === 'article') {
            $this->record->articles()->sync($data['article_ids'] ?? []);
            $this->record->categories()->detach(); // Clear categories if switching to article
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
