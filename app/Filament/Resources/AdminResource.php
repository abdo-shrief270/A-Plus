<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الأسم')
                    ->required()
                    ->minLength(3)
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الألكتروني')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->required(Pages\CreateAdmin::isCurrentPage())
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->label('الأدوار')
                    ->options(fn () => \Spatie\Permission\Models\Role::query()->orderBy('id', 'ASC')->pluck('name', 'name'))
                    ->multiple(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الأسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الألكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('الأدوار')
                    ->state(fn($record) => $record->roles->pluck('name'))
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('نشط')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(!Gate::allows('update_user')),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                ->slideOver(),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'view' => Pages\ViewAdmin::route('/{record}'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }



    public static function getLabel(): ?string
    {
        return 'مستخدم';
    }
    public static function getModelLabel(): string
    {
        return 'مستخدم';
    }
    public static function getPluralLabel(): string
    {
        return 'مستخدمين';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'المستخدمين';
    }
    public static function getNavigationGroup(): string
    {
        return 'النظام';
    }

}
