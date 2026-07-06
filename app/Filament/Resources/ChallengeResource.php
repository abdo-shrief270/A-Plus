<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChallengeResource\Pages;
use App\Models\Challenge;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Read-only view over peer challenges (created via the student API). */
class ChallengeResource extends Resource
{
    protected static ?string $model = Challenge::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $modelLabel = 'تحدي';
    protected static ?string $pluralModelLabel = 'التحديات';
    protected static ?string $navigationGroup = 'المحتوى التعليمي';
    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invite_code')
                    ->label('رمز الدعوة')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.user.name')
                    ->label('المنشئ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_count')
                    ->label('عدد الأسئلة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('المشاركون')
                    ->counts('sessions'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active', 'open' => 'success',
                        'expired' => 'warning',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active', 'open' => 'نشط',
                        'expired' => 'منتهي',
                        'closed' => 'مغلق',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('ينتهي')
                    ->dateTime()
                    ->sortable(),
            ])
            ->bulkActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('تفاصيل التحدي')->schema([
                TextEntry::make('invite_code')->label('رمز الدعوة')->copyable(),
                TextEntry::make('creator.user.name')->label('المنشئ'),
                TextEntry::make('question_count')->label('عدد الأسئلة'),
                TextEntry::make('time_limit_seconds')
                    ->label('الوقت (دقائق)')
                    ->formatStateUsing(fn ($state) => $state ? intdiv((int) $state, 60) : '—'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('expires_at')->label('ينتهي')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->label('أُنشئ')->dateTime(),
            ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChallenges::route('/'),
            'view' => Pages\ViewChallenge::route('/{record}'),
        ];
    }
}
