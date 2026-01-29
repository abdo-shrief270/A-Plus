<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StudentResource\Widgets\StudentStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            null => \Filament\Resources\Components\Tab::make('الكل'),
            'active' => \Filament\Resources\Components\Tab::make('نشط')
                ->modifyQueryUsing(fn($query) => $query->whereHas('user', fn($q) => $q->where('active', true))),
            'inactive' => \Filament\Resources\Components\Tab::make('غير نشط')
                ->modifyQueryUsing(fn($query) => $query->whereHas('user', fn($q) => $q->where('active', false))),
        ];
    }
}
