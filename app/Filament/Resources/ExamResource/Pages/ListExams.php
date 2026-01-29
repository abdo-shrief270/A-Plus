<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExamResource\Widgets\ExamStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            null => \Filament\Resources\Components\Tab::make('الكل'),
            'active' => \Filament\Resources\Components\Tab::make('نشط')
                ->modifyQueryUsing(fn($query) => $query->where('active', true)),
            'inactive' => \Filament\Resources\Components\Tab::make('غير نشط')
                ->modifyQueryUsing(fn($query) => $query->where('active', false)),
        ];
    }
}
