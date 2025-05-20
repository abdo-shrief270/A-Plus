<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المدرسة'),
                Forms\Components\TextInput::make('user_name')
                    ->label('كود المدرسة'),
                Forms\Components\TextInput::make('password')
                    ->nullable()
                    ->label('كلمة السر الخاصة بالمدرسة'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('كود المدرسة')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المدرسة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_schools_count')
                    ->label('عدد الطلاب')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('studentSchools'); // eager load count
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }


    public static function getLabel(): ?string
    {
        return 'مدرسة';
    }
    public static function getModelLabel(): string
    {
        return 'مدرسة';
    }
    public static function getPluralLabel(): string
    {
        return 'مدارس';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'المدراس';
    }
    public static function getNavigationGroup(): string
    {
        return 'المدراس';
    }
}
