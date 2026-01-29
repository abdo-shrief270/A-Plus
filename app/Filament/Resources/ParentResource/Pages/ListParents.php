<?php

namespace App\Filament\Resources\ParentResource\Pages;

use App\Filament\Resources\ParentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParents extends ListRecords
{
    protected static string $resource = ParentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ParentResource\Widgets\ParentStatsOverview::class,
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
