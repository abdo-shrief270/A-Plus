<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->profile()
            ->databaseNotifications()
            ->databaseNotificationsPolling('1s')
            ->font('Cairo')
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'primary' => Color::Emerald,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Resources\StudentResource\Widgets\StudentGenderChart::class,
                \App\Filament\Resources\ExamResource\Widgets\ExamSubjectChart::class,
                \App\Filament\Resources\LessonResource\Widgets\LessonPageTypeChart::class,
                \App\Filament\Resources\QuestionResource\Widgets\QuestionDifficultyChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \GeoSot\FilamentEnvEditor\FilamentEnvEditorPlugin::make()
                    ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                    ->navigationGroup('النظام')
                    ->navigationLabel('متغيرات البيئة')
                    ->navigationIcon('heroicon-o-cog-8-tooth')
                    ->navigationSort(100)
                    ->hideKeys('APP_KEY'),
            ])
            ->authGuard('web')
            ->maxContentWidth(MaxWidth::Full)
            ->spa()
            ->defaultThemeMode(ThemeMode::System)
            ->darkMode()
            ->topNavigation()
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة المستخدمين',
                'المحتوى التعليمي',
                'الدورات والمبيعات',
                'المالية',
                'النظام',
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('سجل النظام')
                    ->url('/log-viewer', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-document-text')
                    ->group('النظام')
                    ->sort(99)
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
