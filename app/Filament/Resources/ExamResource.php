<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Filament\Resources\ExamResource\RelationManagers;
use App\Models\Exam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Filament\Exports\ExamExporter;
use App\Filament\Imports\ExamImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\ExamResource\Widgets\ExamStatsOverview;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'المحتوى التعليمي';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الأختبار'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) =>
                $query->withCount('sections')->withCount('subjects')
            )
            ->headerActions([
                ExportAction::make()->exporter(ExamExporter::class),
                ImportAction::make()->importer(ExamImporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('كود الأختبار')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الأختبار')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sections_count')
                    ->label('عدد الأقسام')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subjects_count')
                    ->label('عدد المواد')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                //                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الاختبار')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الاختبار'),
                        TextEntry::make('id')
                            ->label('كود الاختبار'),
                        TextEntry::make('sections_count')
                            ->label('عدد الأقسام'),
                        TextEntry::make('subjects_count')
                            ->label('عدد المواد'),
                    ])
                    ->columns(2),

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
    public static function getRelations(): array
    {
        //        $record = request()->route('record');
//
//        if (!$record) {
//            return [];
//        }
//        $exam = static::getModel()::withCount('subjects')->withCount('sections')->find($record);
//        if($exam->sections_count > 0)
//        {
//            return [RelationManagers\SectionsRelationManager::class];
//        }elseif ($exam->subjects_count > 0)
//        {
//            return [RelationManagers\SubjectsRelationManager::class];
//        }else
//        {
        return [
            RelationManagers\SectionsRelationManager::class,
            RelationManagers\SubjectsRelationManager::class,
        ];
        //        }

    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'view' => Pages\ViewExam::route('/{record}'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'اختبار';
    }
    public static function getModelLabel(): string
    {
        return 'اختبار';
    }
    public static function getPluralLabel(): string
    {
        return 'اختبارات';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الأختبارات';
    }

    public static function getHeaderWidgets(): array
    {
        return [
            ExamStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'recent' => Tab::make('أضيف حديثاً')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),
        ];
    }
}
