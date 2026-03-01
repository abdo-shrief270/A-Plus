<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid();
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        // Sync categories or articles based on assignment type
        if (($data['assign_to'] ?? 'category') === 'category' && !empty($data['category_ids'])) {
            $this->record->categories()->sync($data['category_ids']);
        }

        if (($data['assign_to'] ?? '') === 'article' && !empty($data['article_ids'])) {
            $this->record->articles()->sync($data['article_ids']);
        }
    }
}
