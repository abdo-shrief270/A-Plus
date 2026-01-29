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
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\StudentResource\Widgets\StudentStatsOverview;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

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
                    ->unique('users', 'phone', ignoreRecord: true)
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
                    ->options(Exam::get()->pluck('name', 'id'))
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
            ->headerActions([
                ExportAction::make()->exporter(UserExporter::class),
                ImportAction::make()->importer(UserImporter::class),
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

                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn($state) => $state === 'male' ? 'ذكر' : 'أنثى'),

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
                Tables\Filters\SelectFilter::make('active')
                    ->label('الحالة')
                    ->options([
                        '1' => 'نشط',
                        '0' => 'غير نشط',
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),
                Tables\Filters\SelectFilter::make('exam_id')
                    ->label('نوع الاختبار')
                    ->options(Exam::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value) => $query->whereHas('student', fn($q) => $q->where('exam_id', $value))
                        );
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('تاريخ التسجيل من'),
                        Forms\Components\DatePicker::make('created_until')->label('تاريخ التسجيل إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم الكامل'),
                        TextEntry::make('user_name')
                            ->label('اسم المستخدم'),
                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->icon('heroicon-o-envelope'),
                        TextEntry::make('phone')
                            ->label('رقم الجوال')
                            ->icon('heroicon-o-phone'),
                        TextEntry::make('gender')
                            ->label('الجنس')
                            ->formatStateUsing(fn($state) => $state === 'male' ? 'ذكر' : 'أنثى'),
                        IconEntry::make('active')
                            ->label('الحالة')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('المعلومات الأكاديمية')
                    ->schema([
                        TextEntry::make('student.exam.name')
                            ->label('نوع الاختبار'),
                        TextEntry::make('student.exam_date')
                            ->label('تاريخ الاختبار')
                            ->date(),
                        TextEntry::make('student.id_number')
                            ->label('رقم الهوية'),
                    ])
                    ->columns(3),

                Section::make('معلومات النظام')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الاضافة')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ اخر تعديل')
                            ->dateTime(),
                    ])
                    ->columns(2),
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
        return static::getModel()::where('type', 'student')->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'student');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
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
}
