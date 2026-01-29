<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Models\Role;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AdminResource\Widgets\AdminStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        $data = [
            null => Tab::make('الكل')->query(fn($query) => $query)
        ];
        foreach (\Spatie\Permission\Models\Role::orderBy('id', 'ASC')->select('name')->pluck('name')->all() as $role) {
            $data[$role] = Tab::make()->query(fn($query) => $query->role($role));
        }
        return $data;
    }
}
