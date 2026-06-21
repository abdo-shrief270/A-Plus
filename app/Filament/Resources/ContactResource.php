<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ContactExporter;
use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Filament\Resources\ContactResource\Widgets\ContactStatsOverview;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المُرسِل')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('المستخدم')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('الرسالة')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('الموضوع')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'open' => 'مفتوحة',
                                'pending' => 'قيد المتابعة',
                                'resolved' => 'تم حلها',
                                'closed' => 'مغلقة',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('category')
                            ->label('الفئة')
                            ->options([
                                'inquiry' => 'استفسار',
                                'complaint' => 'شكوى',
                                'suggestion' => 'اقتراح',
                                'technical' => 'مشكلة تقنية',
                                'billing' => 'الدفع والفواتير',
                                'question_report' => 'الإبلاغ عن سؤال',
                                'other' => 'أخرى',
                            ])
                            ->default('inquiry')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->label('محتوى الرسالة')
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exporter(ContactExporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('الموضوع')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->placeholder(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'primary' => 'open',
                        'warning' => 'pending',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'open' => 'مفتوحة',
                        'pending' => 'قيد المتابعة',
                        'resolved' => 'تم حلها',
                        'closed' => 'مغلقة',
                        default => $state ?? '—',
                    }),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('الفئة')
                    ->colors([
                        'primary' => 'inquiry',
                        'danger' => 'complaint',
                        'success' => 'suggestion',
                        'warning' => 'technical',
                        'info' => 'billing',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'inquiry' => 'استفسار',
                        'complaint' => 'شكوى',
                        'suggestion' => 'اقتراح',
                        'technical' => 'مشكلة تقنية',
                        'billing' => 'الدفع والفواتير',
                        'question_report' => 'الإبلاغ عن سؤال',
                        'other' => 'أخرى',
                        default => $state ?? '—',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('replies_count')
                    ->label('الردود')
                    ->counts('replies')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_reply_at')
                    ->label('آخر رد')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوحة',
                        'pending' => 'قيد المتابعة',
                        'resolved' => 'تم حلها',
                        'closed' => 'مغلقة',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('الفئة')
                    ->options([
                        'inquiry' => 'استفسار',
                        'complaint' => 'شكوى',
                        'suggestion' => 'اقتراح',
                        'technical' => 'مشكلة تقنية',
                        'billing' => 'الدفع والفواتير',
                        'question_report' => 'الإبلاغ عن سؤال',
                        'other' => 'أخرى',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('quick_reply')
                    ->label('رد')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (\App\Models\Contact $record): bool => $record->status !== 'closed')
                    ->modalHeading(fn (\App\Models\Contact $record): string => 'الرد على: ' . ($record->subject ?? '—'))
                    ->modalSubmitActionLabel('إرسال')
                    ->form([
                        Forms\Components\Textarea::make('body')
                            ->label('نص الرد')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('صور مرفقة (اختياري)')
                            ->multiple()
                            ->image()
                            ->maxFiles(10)
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('contact_attachments/staff')
                            ->visibility('public')
                            ->reorderable()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('next_status')
                            ->label('حالة الرسالة بعد الإرسال')
                            ->options([
                                'pending' => 'بانتظار رد المستخدم',
                                'resolved' => 'تم حلها',
                                'closed' => 'إغلاق',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (\App\Models\Contact $record, array $data): void {
                        $reply = \App\Models\ContactReply::create([
                            'contact_id' => $record->id,
                            'user_id' => \Filament\Facades\Filament::auth()->id(),
                            'body' => $data['body'],
                            'is_staff' => true,
                        ]);

                        foreach (($data['attachments'] ?? []) as $path) {
                            \App\Models\ContactAttachment::create([
                                'attachable_type' => \App\Models\ContactReply::class,
                                'attachable_id' => $reply->id,
                                'path' => $path,
                                'original_name' => basename($path),
                            ]);
                        }

                        $record->forceFill([
                            'last_reply_at' => now(),
                            'status' => $data['next_status'],
                        ])->save();

                        if ($record->user) {
                            $record->user->notify(new \App\Notifications\SimpleNotification(
                                title: 'رد جديد على رسالتك',
                                description: ($record->subject ?: 'رسالة تواصل') . ' — تم الرد من فريق الدعم',
                                link: '/dashboard/tickets/' . $record->id,
                                color: 'info',
                                icon: 'i-heroicons-chat-bubble-left-right',
                            ));
                        }
                    }),
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\Action::make('close')
                    ->label('إغلاق')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (\App\Models\Contact $record): bool => $record->status !== 'closed')
                    ->requiresConfirmation()
                    ->action(fn (\App\Models\Contact $record) => $record->update(['status' => 'closed'])),
                Tables\Actions\Action::make('reopen')
                    ->label('إعادة فتح')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (\App\Models\Contact $record): bool => $record->status === 'closed')
                    ->requiresConfirmation()
                    ->action(fn (\App\Models\Contact $record) => $record->update(['status' => 'open'])),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
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
            RelationManagers\RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        // Admins don't create contact messages — those come from users via the
        // public contact form or the dashboard tickets flow. Listing, viewing,
        // and editing (including reply via the relation manager) are enough.
        return [
            'index' => Pages\ListContacts::route('/'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getLabel(): ?string
    {
        return 'رسالة تواصل';
    }

    public static function getPluralLabel(): ?string
    {
        return 'رسائل التواصل';
    }

    public static function getHeaderWidgets(): array
    {
        return [
            ContactStatsOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'open')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Tabs live on the ListRecords page class in Filament v3, not on the
    // Resource. See ContactResource/Pages/ListContacts.php for getTabs().
}
