<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers\SchoolStudentsRelationManager;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Exports\SchoolExporter;
use App\Filament\Imports\SchoolImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\SchoolResource\Widgets\SchoolStatsOverview;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المدرسة'),

                Forms\Components\TextInput::make('user_name')
                    ->label('كود المدرسة')
                    ->maxLength(255)
                    ->required()
                    ->live(debounce: 100)
                    ->helperText(function ($state, callable $get) {
                        if (!$state) {
                            return null;
                        }

                        if (strlen($state) < 5) {
                            return 'كود المدرسة يجب أن يكون 5 حروف على الأقل ❌';
                        }

                        // Get the current record ID if editing
                        $currentId = $get('id'); // This works in edit mode
            
                        $query = \App\Models\School::where('user_name', $state);
                        if ($currentId) {
                            $query->where('id', '!=', $currentId); // Ignore current record
                        }

                        if ($query->exists()) {
                            return 'كود المدرسة مستخدم بالفعل ❌';
                        }

                        return 'كود المدرسة متاح ✅';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exporter(SchoolExporter::class),
                ImportAction::make()->importer(SchoolImporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_school_count')
                    ->label('عدد الطلاب')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('نشط')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الاضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ اخر تعديل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('الحالة')
                    ->options([
                        '1' => 'نشط',
                        '0' => 'غير نشط',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            SchoolStudentsRelationManager::class,
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('studentSchool'); // eager load count
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
        return 'المستخدمين';
    }
}
