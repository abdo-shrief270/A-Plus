<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->label('الطالب'),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->required()
                    ->label('الدورة'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                    ])
                    ->required()
                    ->default('active'),
                Forms\Components\DateTimePicker::make('enrolled_at')
                    ->label('تاريخ التسجيل')
                    ->default(now())
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('تاريخ الانتهاء'),
                Forms\Components\TextInput::make('created_by')
                    ->label('تم الإنشاء بواسطة')
                    ->default('admin')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                    }),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                    ]),
                Tables\Filters\SelectFilter::make('course')
                    ->label('الدورة')
                    ->relationship('course', 'title'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Forms\Form|\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('معلومات الطالب')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label('اسم الطالب')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('user.email')
                            ->label('البريد الإلكتروني')
                            ->icon('heroicon-m-envelope'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make('معلومات الدورة')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('course.title')
                            ->label('عنوان الدورة')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('course.price')
                            ->label('سعر الدورة')
                            ->money('SAR'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make('تفاصيل التسجيل')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('enrolled_at')
                            ->label('تاريخ التسجيل')
                            ->date(),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'active' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'active' => 'نشط',
                                'pending' => 'معلق',
                                'cancelled' => 'ملغي',
                            }),
                    ])->columns(2),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: 0;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'تسجيلات معلقة';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'view' => Pages\ViewEnrollment::route('/{record}'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
