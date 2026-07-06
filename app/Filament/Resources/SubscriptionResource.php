<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Plan;
use App\Models\Student;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $modelLabel = 'اشتراك';
    protected static ?string $pluralModelLabel = 'الاشتراكات';
    protected static ?string $navigationGroup = 'الدورات والمبيعات';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label('الطالب')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => Student::whereHas(
                    'user',
                    fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                )->with('user')->limit(20)->get()
                    ->mapWithKeys(fn (Student $s) => [$s->id => $s->user?->name ?? "طالب #{$s->id}"]))
                ->getOptionLabelUsing(fn ($value) => Student::with('user')->find($value)?->user?->name ?? "طالب #{$value}"),
            Forms\Components\Select::make('plan_id')
                ->label('الباقة')
                ->required()
                ->options(Plan::orderBy('name')->pluck('name', 'id'))
                ->searchable(),
            Forms\Components\Select::make('status')
                ->label('الحالة')
                ->required()
                ->default('pending')
                ->options([
                    'pending' => 'معلق',
                    'active' => 'نشط',
                    'expired' => 'منتهي',
                    'cancelled' => 'ملغى',
                ]),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('يبدأ في')
                ->default(now()),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('ينتهي في')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('الباقة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'subscription' => 'info',
                        'pack' => 'warning',
                        'trial' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'subscription' => 'اشتراك',
                        'pack' => 'باقة نقاط',
                        'trial' => 'تجربة',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('يبدأ')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('ينتهي')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('الباقة')
                    ->relationship('plan', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('تفاصيل الاشتراك')->schema([
                TextEntry::make('student.user.name')->label('الطالب'),
                TextEntry::make('plan.name')->label('الباقة'),
                TextEntry::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                        default => $state,
                    }),
                TextEntry::make('starts_at')->label('يبدأ')->dateTime(),
                TextEntry::make('ends_at')->label('ينتهي')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->label('أُنشئ')->dateTime(),
            ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
