<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class StudentEnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments'; // Assuming Student (User) hasMany Enrollments

    protected static ?string $title = 'الاشتراكات والدورات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'title') // Assuming Course model has a 'title'
                    ->required()
                    ->label('الدورة'),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'نشط',
                        'pending' => 'قيد الانتظار',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى'
                    ])
                    ->required()
                    ->label('الحالة'),
                Forms\Components\DatePicker::make('enrolled_at')
                    ->label('تاريخ الاشتراك')
                    ->default(now()),
                Forms\Components\DatePicker::make('expires_at')
                    ->label('تاريخ الانتهاء'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'نشط',
                        'pending' => 'قيد الانتظار',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغى',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('enrolled_at')
                    ->label('تاريخ الاشتراك')
                    ->date()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة اشتراك'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
