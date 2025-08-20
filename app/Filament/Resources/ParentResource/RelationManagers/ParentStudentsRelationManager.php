<?php
namespace App\Filament\Resources\ParentResource\RelationManagers;

use App\Models\Student;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParentStudentsRelationManager extends RelationManager
{
    protected static ?string $title = "الأبناء";

    protected static ?string $icon = 'heroicon-o-user-group';

    protected static string $relationship = 'studentParent';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('parent_id')
                    ->default(fn () => $this->ownerRecord->id),

                Forms\Components\Select::make('student_id')
                    ->label('اختر الطالب')
                    ->options(function () {
                        return Student::query()
                            ->whereDoesntHave('studentParent')
                            ->with('user')
                            ->get()
                            ->pluck('user.name', 'id');
                    })
                    ->searchable()
                    ->required(),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
            $query->with(['student.user', 'student.exam'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الاسم الكامل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.user.user_name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.user.phone')
                    ->label('رقم الجوال')
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.user.gender')
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

                Tables\Columns\ToggleColumn::make('student.user.active')
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
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة ابن جديد')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getLabel(): ?string
    {
        return 'ابن';
    }
    public static function getModelLabel(): string
    {
        return 'ابن';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ابناء';
    }

}
