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

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'طلبات الحذف';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'طلب حذف';

    protected static ?string $pluralModelLabel = 'طلبات الحذف';

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
                    ->label('الطالب')
                    ->disabled(),
                Forms\Components\Select::make('requested_by')
                    ->relationship('requester', 'name')
                    ->label('مقدم الطلب')
                    ->disabled(),
                Forms\Components\Textarea::make('reason')
                    ->label('السبب')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('مقدم الطلب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->reason),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('المراجع')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على طلب الحذف')
                    ->modalDescription('سيتم حذف الطالب نهائياً. هل أنت متأكد؟')
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
                            ->title('تم حذف الطالب بنجاح')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('رفض طلب الحذف')
                    ->modalDescription('هل أنت متأكد من رفض هذا الطلب؟')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('تم رفض طلب الحذف')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
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

    public static function getLabel(): ?string
    {
        return 'طلب حذف';
    }

    public static function getModelLabel(): string
    {
        return 'طلب حذف';
    }

    public static function getPluralLabel(): string
    {
        return 'طلبات الحذف';
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'طلبات الحذف';
    }
}
