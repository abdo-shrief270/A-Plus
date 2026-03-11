<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LatexFormatResource\Pages;
use App\Models\LatexFormat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class LatexFormatResource extends Resource
{
    protected static ?string $model = LatexFormat::class;

    protected static ?string $navigationIcon = 'heroicon-o-variable';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'المحتوى التعليمي';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات القالب')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('المفتاح')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('مفتاح فريد للقالب (بالإنجليزية)')
                            ->regex('/^[a-zA-Z0-9_]+$/')
                            ->validationMessages([
                                'regex' => 'المفتاح يجب أن يحتوي على حروف إنجليزية وأرقام فقط',
                            ]),

                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->required()
                            ->options([
                                'الكسور' => 'الكسور',
                                'الجذور والأسس' => 'الجذور والأسس',
                                'العمليات الحسابية' => 'العمليات الحسابية',
                                'التحليل' => 'التحليل',
                                'اللوغاريتمات والأسية' => 'اللوغاريتمات والأسية',
                                'الدوال المثلثية' => 'الدوال المثلثية',
                                'المصفوفات والمتجهات' => 'المصفوفات والمتجهات',
                                'الهندسة' => 'الهندسة',
                                'المجموعات' => 'المجموعات',
                                'الأقواس والحدود' => 'الأقواس والحدود',
                                'الرموز الشائعة' => 'الرموز الشائعة',
                                'النصوص والتنسيق' => 'النصوص والتنسيق',
                                'يدوي' => 'يدوي',
                            ])
                            ->searchable()
                            ->native(false),

                        Forms\Components\TextInput::make('icon')
                            ->label('الأيقونة (LaTeX)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('كود LaTeX للعرض في القائمة مثل: \\(\\frac{أ}{ب}\\)')
                            ->extraInputAttributes(['dir' => 'ltr']),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعّل')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('القالب والمدخلات')
                    ->schema([
                        Forms\Components\TextInput::make('template')
                            ->label('قالب LaTeX')
                            ->required()
                            ->helperText('استخدم %key% كمتغيرات. مثال: \\frac{%n%}{%d%}')
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->columnSpanFull(),

                        Forms\Components\ViewField::make('template_preview')
                            ->view('filament.forms.latex-template-preview')
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('inputs')
                            ->label('حقول الإدخال')
                            ->schema([
                                Forms\Components\TextInput::make('k')
                                    ->label('المفتاح')
                                    ->required()
                                    ->maxLength(20)
                                    ->helperText('يجب أن يطابق %key% في القالب'),

                                Forms\Components\TextInput::make('l')
                                    ->label('التسمية')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('p')
                                    ->label('القيمة الافتراضية')
                                    ->maxLength(100)
                                    ->extraInputAttributes(['dir' => 'ltr']),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('المفتاح')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'الكسور' => 'primary',
                        'الجذور والأسس' => 'success',
                        'العمليات الحسابية' => 'warning',
                        'التحليل' => 'danger',
                        'اللوغاريتمات والأسية' => 'info',
                        'الدوال المثلثية' => 'gray',
                        'المصفوفات والمتجهات' => 'primary',
                        'الهندسة' => 'success',
                        'المجموعات' => 'warning',
                        'الأقواس والحدود' => 'danger',
                        'الرموز الشائعة' => 'info',
                        'النصوص والتنسيق' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('template')
                    ->label('القالب')
                    ->limit(40)
                    ->fontFamily('mono')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(fn () => LatexFormat::query()
                        ->distinct()
                        ->pluck('category', 'category')
                        ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('مفعّل')
                    ->falseLabel('معطّل'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (LatexFormat $record) => $record->is_active ? 'تعطيل' : 'تفعيل')
                        ->icon(fn (LatexFormat $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (LatexFormat $record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn (LatexFormat $record) => $record->update(['is_active' => !$record->is_active]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات القالب')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم'),
                        TextEntry::make('key')
                            ->label('المفتاح'),
                        TextEntry::make('category')
                            ->label('التصنيف')
                            ->badge(),
                        TextEntry::make('icon')
                            ->label('الأيقونة'),
                        TextEntry::make('sort_order')
                            ->label('الترتيب'),
                        TextEntry::make('is_active')
                            ->label('الحالة')
                            ->formatStateUsing(fn (bool $state) => $state ? 'مفعّل' : 'معطّل')
                            ->badge()
                            ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                    ])
                    ->columns(3),

                Section::make('القالب')
                    ->schema([
                        TextEntry::make('template')
                            ->label('قالب LaTeX'),
                        TextEntry::make('inputs')
                            ->label('حقول الإدخال')
                            ->getStateUsing(function ($record) {
                                $inputs = $record->inputs;
                                if (empty($inputs)) return 'بدون مدخلات';
                                return collect($inputs)->map(fn ($i) => "{$i['l']} ({$i['k']})")->join(' ، ');
                            }),
                    ]),

                Section::make('معلومات النظام')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإضافة')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ آخر تعديل')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLatexFormats::route('/'),
            'create' => Pages\CreateLatexFormat::route('/create'),
            'view' => Pages\ViewLatexFormat::route('/{record}'),
            'edit' => Pages\EditLatexFormat::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'قالب معادلة';
    }

    public static function getModelLabel(): string
    {
        return 'قالب معادلة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'قوالب المعادلات';
    }

    public static function getPluralLabel(): string
    {
        return 'قوالب المعادلات';
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'قوالب المعادلات';
    }
}
