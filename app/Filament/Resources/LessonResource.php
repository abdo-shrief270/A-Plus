<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ColorEntry;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'خطة المذاكرة';

    protected static ?string $modelLabel = 'درس';

    protected static ?string $pluralModelLabel = 'الدروس';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        Forms\Components\Select::make('exam_id')
                            ->label('الامتحان')
                            ->relationship('exam', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الدرس')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التخصيص والمظهر')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('شعار الدرس')
                            ->image()
                            ->directory('lesson-logos')
                            ->imageEditor()
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('color')
                            ->label('لون الدرس')
                            ->default('#10B981')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('ترتيب الدرس ضمن الامتحان (الأقل أولاً)'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة المتوقعة (بالدقائق)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->suffix('دقيقة'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('الدروس غير النشطة لن تظهر في خطة المذاكرة'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam.name')
                    ->label('الامتحان')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\ImageColumn::make('logo')
                    ->label('الشعار')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الدرس')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('اللون'),

                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pages_count')
                    ->label('عدد الصفحات')
                    ->counts('pages')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_id')
                    ->label('الامتحان')
                    ->relationship('exam', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('معاينة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => "معاينة: {$record->title}")
                    ->modalContent(fn($record) => view('filament.resources.lesson-resource.preview', ['lesson' => $record]))
                    ->modalWidth('7xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        TextEntry::make('title')
                            ->label('عنوان الدرس')
                            ->weight('bold'),
                        TextEntry::make('exam.name')
                            ->label('الامتحان'),
                        TextEntry::make('description')
                            ->label('وصف الدرس')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->columns(2),

                Section::make('التخصيص والمظهر')
                    ->schema([
                        ImageEntry::make('logo')
                            ->label('شعار الدرس')
                            ->circular(),
                        ColorEntry::make('color')
                            ->label('لون الدرس'),
                    ])
                    ->columns(2),

                Section::make('الإعدادات')
                    ->schema([
                        TextEntry::make('order')
                            ->label('الترتيب')
                            ->badge(),
                        TextEntry::make('duration_minutes')
                            ->label('المدة المتوقعة')
                            ->suffix(' دقيقة'),
                        IconEntry::make('is_active')
                            ->label('نشط')
                            ->boolean(),
                        TextEntry::make('pages_count')
                            ->label('عدد الصفحات')
                            ->state(fn($record) => $record->pages()->count()),
                    ])
                    ->columns(4),

                Section::make('معلومات النظام')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ آخر تعديل')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
