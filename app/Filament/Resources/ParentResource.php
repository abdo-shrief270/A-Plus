<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentResource\Pages;
use App\Filament\Resources\ParentResource\RelationManagers;
use App\Models\Parentt;
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

class ParentResource extends Resource
{
    protected static ?string $model = Parentt::class;

    protected static ?int $navigationSort = 3;
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
                    ->maxLength(255)
                    ->required(),

                Forms\Components\Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ])
                    ->required(),

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
                    ->formatStateUsing(fn($state) => $state === 'male' ? 'ذكر' : 'أنثى'),

                Tables\Columns\TextColumn::make('student_parent_count')
                    ->label('عدد الأبناء'),

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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
                        TextEntry::make('student_parent_count')
                            ->label('عدد الأبناء'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ParentStudentsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('studentParent');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParents::route('/'),
            'create' => Pages\CreateParent::route('/create'),
            'view' => Pages\ViewParent::route('/{record}'),
            'edit' => Pages\EditParent::route('/{record}/edit'),
        ];
    }
    public static function getLabel(): ?string
    {
        return 'ولي أمر';
    }
    public static function getModelLabel(): string
    {
        return 'ولي أمر';
    }
    public static function getPluralLabel(): string
    {
        return 'أولياء أمور';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'أولياء الأمور';
    }
}
