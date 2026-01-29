<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Actions\Action;
use App\Models\Setting;
use Filament\Notifications\Notification;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إعدادات النظام';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Load settings
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('عام')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('اسم الموقع')
                                    ->required(),
                                Forms\Components\FileUpload::make('site_logo')
                                    ->label('شعار الموقع')
                                    ->image()
                                    ->directory('settings')
                                    ->preserveFilenames(),
                                Forms\Components\Textarea::make('site_description')
                                    ->label('وصف الموقع')
                                    ->rows(3),
                            ]),
                        Forms\Components\Tabs\Tab::make('بوابات الدفع')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\CheckboxList::make('active_gateways')
                                    ->label('تفعيل بوابات الدفع')
                                    ->options([
                                        'stripe' => 'Stripe',
                                        'paypal' => 'PayPal',
                                        'myfatoorah' => 'MyFatoorah (ماي فاتورة)',
                                        'tap' => 'Tap Payments (تاب)',
                                        'tamara' => 'Tamara (تمارا)',
                                        'tabby' => 'Tabby (تابي)',
                                        'paytabs' => 'PayTabs (باي تابس)',
                                    ])
                                    ->columns(4)
                                    ->live(),

                                Forms\Components\Section::make('Stripe')
                                    ->aside()
                                    ->description('إعدادات بوابة Stripe')
                                    ->schema([
                                        Forms\Components\TextInput::make('stripe_key')
                                            ->label('المفتاح العام (Public Key)'),
                                        Forms\Components\TextInput::make('stripe_secret')
                                            ->label('المفتاح السري (Secret Key)')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('stripe_webhook_secret')
                                            ->label('مفتاح الويب هوك (Webhook Secret)')
                                            ->password()
                                            ->revealable(),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('stripe', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('PayPal')
                                    ->aside()
                                    ->description('إعدادات بوابة PayPal')
                                    ->schema([
                                        Forms\Components\TextInput::make('paypal_client_id')
                                            ->label('معرّف العميل (Client ID)'),
                                        Forms\Components\TextInput::make('paypal_secret')
                                            ->label('الرقم السري (Secret)')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Select::make('paypal_mode')
                                            ->label('الوضع')
                                            ->options([
                                                'sandbox' => 'تجريبي (Sandbox)',
                                                'live' => 'حقيقي (Live)',
                                            ])
                                            ->default('sandbox'),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('paypal', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('MyFatoorah (ماي فاتورة)')
                                    ->aside()
                                    ->description('إعدادات بوابة MyFatoorah')
                                    ->schema([
                                        Forms\Components\TextInput::make('myfatoorah_token')
                                            ->label('API Token')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Select::make('myfatoorah_mode')
                                            ->label('الوضع')
                                            ->options([
                                                'test' => 'تجريبي (Test)',
                                                'live' => 'حقيقي (Live)',
                                            ])
                                            ->default('test'),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('myfatoorah', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('Tap Payments (تاب)')
                                    ->aside()
                                    ->description('إعدادات بوابة Tap')
                                    ->schema([
                                        Forms\Components\TextInput::make('tap_secret_key')
                                            ->label('Secret Key (sk_...)')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('tap_publishable_key')
                                            ->label('Publishable Key (pk_...)'),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('tap', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('Tamara (تمارا)')
                                    ->aside()
                                    ->description('إعدادات بوابة Tamara')
                                    ->schema([
                                        Forms\Components\TextInput::make('tamara_api_token')
                                            ->label('API Token')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('tamara_notification_token')
                                            ->label('Notification Token')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Select::make('tamara_mode')
                                            ->label('الوضع')
                                            ->options([
                                                'sandbox' => 'تجريبي (Sandbox)',
                                                'live' => 'حقيقي (Live)',
                                            ])
                                            ->default('sandbox'),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('tamara', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('Tabby (تابي)')
                                    ->aside()
                                    ->description('إعدادات بوابة Tabby')
                                    ->schema([
                                        Forms\Components\TextInput::make('tabby_public_key')
                                            ->label('Public Key (pk_...)'),
                                        Forms\Components\TextInput::make('tabby_secret_key')
                                            ->label('Secret Key (sk_...)')
                                            ->password()
                                            ->revealable(),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('tabby', $get('active_gateways') ?? [])),

                                Forms\Components\Section::make('PayTabs (باي تابس)')
                                    ->aside()
                                    ->description('إعدادات بوابة PayTabs')
                                    ->schema([
                                        Forms\Components\TextInput::make('paytabs_profile_id')
                                            ->label('Profile ID'),
                                        Forms\Components\TextInput::make('paytabs_server_key')
                                            ->label('Server Key')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('paytabs_client_key')
                                            ->label('Client Key'),
                                    ])
                                    ->visible(fn(Forms\Get $get) => in_array('paytabs', $get('active_gateways') ?? [])),
                            ]),
                        Forms\Components\Tabs\Tab::make('قانوني')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Forms\Components\RichEditor::make('terms_of_service')
                                    ->label('شروط الخدمة'),
                                Forms\Components\RichEditor::make('privacy_policy')
                                    ->label('سياسة الخصوصية'),
                            ]),
                        Forms\Components\Tabs\Tab::make('إعدادات مخصصة')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\Repeater::make('custom_settings')
                                    ->label('إعدادات إضافية')
                                    ->schema([
                                        Forms\Components\TextInput::make('key')
                                            ->label('المفتاح (Key)')
                                            ->required(),
                                        Forms\Components\TextInput::make('value')
                                            ->label('القيمة (Value)')
                                            ->required(),
                                        Forms\Components\Select::make('type')
                                            ->label('النوع')
                                            ->options([
                                                'text' => 'نص',
                                                'boolean' => 'منطقي (True/False)',
                                                'image' => 'صورة',
                                                'json' => 'JSON',
                                                'select' => 'قائمة (Select)',
                                                'number' => 'رقم',
                                            ])
                                            ->default('text'),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('إضافة إعداد جديد'),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('submit'),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Determine group based on key prefix or manual logic
            $group = 'general';
            if (
                str_starts_with($key, 'stripe_') ||
                str_starts_with($key, 'paypal_') ||
                str_starts_with($key, 'myfatoorah_') ||
                str_starts_with($key, 'tap_') ||
                str_starts_with($key, 'tamara_') ||
                str_starts_with($key, 'tabby_') ||
                str_starts_with($key, 'paytabs_') ||
                $key === 'active_gateways'
            ) {
                $group = 'payment';
            } elseif (in_array($key, ['terms_of_service', 'privacy_policy'])) {
                $group = 'legal';
            }

            // Determine type
            $type = 'text';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_numeric($value)) {
                $type = 'number';
            } elseif (is_array($value)) {
                $type = 'json';
            } elseif (in_array($key, ['site_logo', 'favicon'])) {
                $type = 'image';
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $group,
                    'type' => $type
                ]
            );
        }

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }
}
