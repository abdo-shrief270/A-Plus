<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueResource\Pages;
use App\Filament\Resources\LeagueResource\RelationManagers;
use App\Models\League;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeagueResource extends Resource
{
    protected static ?string $model = League::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'التلعيب (Gamification)';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'دوري';

    protected static ?string $pluralModelLabel = 'الدوريات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الدوري')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('icon_path')
                    ->image()
                    ->directory('leagues')
                    ->label('الأيقونة'),
                Forms\Components\TextInput::make('min_score')
                    ->label('أقل نقاط مطلوبة')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\ColorPicker::make('color')
                    ->label('لون التميز')
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->label('الترتيب')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')
                    ->label('الأيقونة'),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدوري')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_score')
                    ->label('أقل نقاط')
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('اللون'),
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeagues::route('/'),
            'create' => Pages\CreateLeague::route('/create'),
            'edit' => Pages\EditLeague::route('/{record}/edit'),
        ];
    }
}
