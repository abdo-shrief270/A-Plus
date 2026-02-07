<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentDeletionRequestResource\Pages;
use App\Models\StudentDeletionRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class StudentDeletionRequestResource extends Resource
{
    protected static ?string $model = StudentDeletionRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?string $navigationLabel = 'Deletion Requests';

    protected static ?int $navigationSort = 100;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student.user', 'name')
                    ->label('Student')
                    ->disabled(),
                Forms\Components\Select::make('requested_by')
                    ->relationship('requester', 'name')
                    ->label('Requested By')
                    ->disabled(),
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->reason),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Deletion Request')
                    ->modalDescription('This will permanently delete the student. Are you sure?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        // Delete the student
                        $record->student?->user?->delete();
                        $record->student?->delete();

                        Notification::make()
                            ->title('Student deleted successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Deletion request rejected')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentDeletionRequests::route('/'),
            'view' => Pages\ViewStudentDeletionRequest::route('/{record}'),
        ];
    }
}
