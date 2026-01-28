<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Filament\Actions\Exports\Models\Export::polymorphicUserRelationship();
        \Filament\Actions\Imports\Models\Import::polymorphicUserRelationship();

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'App\Models\User' => \App\Models\User::class,
            'App\Models\Admin' => \App\Models\Admin::class,
        ]);
    }
}
