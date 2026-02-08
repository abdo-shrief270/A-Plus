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
        $this->app->bind(
            \App\Interfaces\PaymentGatewayInterface::class,
            \App\Services\Payment\MockPaymentGateway::class
        );
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

        // Register model observers for notifications
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Student::observe(\App\Observers\StudentObserver::class);
        \App\Models\School::observe(\App\Observers\SchoolObserver::class);

        // Log Viewer authorization - super_admin only
        \Opcodes\LogViewer\Facades\LogViewer::auth(function ($request) {
            $user = auth('web')->user();
            return $user && $user->hasRole('super_admin');
        });
    }
}
