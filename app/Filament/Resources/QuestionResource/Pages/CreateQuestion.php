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
        $formData = $this->data;

        // Sync categories or articles based on assignment type
        $assignTo = $formData['assign_to'] ?? 'category';

        if ($assignTo === 'category' && !empty($formData['category_ids'])) {
            $this->record->categories()->sync($formData['category_ids']);
        }

        if ($assignTo === 'article' && !empty($formData['article_ids'])) {
            $this->record->articles()->sync($formData['article_ids']);
        }
    }
}
