<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages as ResourcePages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'الإعدادات والمحتوى';
    protected static ?int $navigationSort = 1;

    public static function getLabel(): ?string
    {
        return 'صفحة';
    }

    public static function getPluralLabel(): ?string
    {
        return 'صفحات الموقع';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الصفحة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('المعرف (slug)')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($record): bool => $record?->is_locked ?? false)
                            ->helperText('يُستخدم في رابط الصفحة، مثلاً: about → /about'),
                        Forms\Components\Toggle::make('is_published')
                            ->label('منشورة')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('المحتوى')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('محتوى الصفحة')
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3', 'bulletList', 'orderedList',
                                'link', 'blockquote', 'codeBlock', 'redo', 'undo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->label('الرابط')
                    ->formatStateUsing(fn (string $state): string => '/' . $state)
                    ->color('primary')
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشورة')
                    ->boolean(),
                Tables\Columns\TextColumn::make('content')
                    ->label('معاينة')
                    ->html()
                    ->limit(120)
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (Page $record): bool => !$record->is_locked),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ResourcePages\ListPages::route('/'),
            'create' => ResourcePages\CreatePage::route('/create'),
            'edit' => ResourcePages\EditPage::route('/{record}/edit'),
        ];
    }
}
