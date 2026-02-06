<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'subscription' => Tab::make('الاشتراكات')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'subscription')),
            'pack' => Tab::make('باقات النقاط')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'pack')),
            'active' => Tab::make('نشط فقط')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', true)),
        ];
    }
}
