<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

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

        // Register Activity observer for sensitive action notifications
        \Spatie\Activitylog\Models\Activity::observe(\App\Observers\ActivityNotificationObserver::class);

        // Log Viewer authorization - super_admin only
        \Opcodes\LogViewer\Facades\LogViewer::auth(function ($request) {
            $user = auth('web')->user();
            return $user && $user->hasRole('super_admin');
        });

        // Scramble Security Scheme
        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        FilamentAsset::register([
            Css::make('katex-css', 'https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.16.9/katex.min.css'),
        ]);

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.end',
            fn () => new \Illuminate\Support\HtmlString('
                <script defer src="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.16.9/katex.min.js"></script>
                <script defer src="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.16.9/contrib/auto-render.min.js"
                    onload="if(typeof renderMathInElement!==\'undefined\'){document.querySelectorAll(\'.math-content,.prose\').forEach(function(el){try{renderMathInElement(el,{delimiters:[{left:\'$$\',right:\'$$\',display:true},{left:\'$\',right:\'$\',display:false},{left:\'\\\\(\',right:\'\\\\)\',display:false},{left:\'\\\\[\',right:\'\\\\]\',display:true}],throwOnError:false})}catch(e){}})}"></script>
                <script defer src="' . asset('js/katex-init.js') . '"></script>
            '),
        );
    }
}
