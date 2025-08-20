<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم الكامل')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('user_name')
                    ->label('اسم المستخدم')
                    ->maxLength(255)
                    ->required()
                    ->live(debounce: 100)
                    ->helperText(function ($state, callable $get) {
                        if (!$state) {
                            return null;
                        }

                        if (strlen($state) < 5) {
                            return 'اسم المستخدم يجب أن يكون 5 حروف على الأقل ❌';
                        }

                        // Get the current record ID if editing
                        $currentId = $get('id'); // This works in edit mode

                        $query = \App\Models\User::where('user_name', $state);
                        if ($currentId) {
                            $query->where('id', '!=', $currentId); // Ignore current record
                        }

                        if ($query->exists()) {
                            return 'اسم المستخدم مستخدم بالفعل ❌';
                        }

                        return 'اسم المستخدم متاح ✅';
                    }),

                Forms\Components\TextInput::make('phone')
                    ->label('رقم الجوال')
                    ->tel()
                    ->unique('users','phone',ignoreRecord: true)
                    ->maxLength(20)
                    ->live()
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->required(),

                Forms\Components\Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ])
                    ->required(),

                Forms\Components\Select::make('exam_id')
                    ->label('نوع الاختبار')
                    ->options(Exam::get()->pluck('name','id'))
                    ->default(null),

                Forms\Components\DatePicker::make('exam_date')
                    ->label('تاريخ الاختبار'),

                Forms\Components\TextInput::make('id_number')
                    ->label('رقم الهوية')
                    ->maxLength(255)
                    ->default(null),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn ($state) => $state === 'male' ? 'ذكر' : 'أنثى'),

                Tables\Columns\TextColumn::make('student.exam.name')
                ->label('نوع الاختبار')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.exam_date')
                    ->label('تاريخ الاختبار')
                    ->date(),

                Tables\Columns\TextColumn::make('student.id_number')
                    ->label('رقم الهوية'),

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return static::getModel()::where('type','student')->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type','student');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
//            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }


    public static function getLabel(): ?string
    {
        return 'طالب';
    }
    public static function getModelLabel(): string
    {
        return 'طالب';
    }
    public static function getPluralLabel(): string
    {
        return 'طلبة';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الطلبة';
    }
    public static function getNavigationGroup(): string
    {
        return 'المستخدمين';
    }
}
