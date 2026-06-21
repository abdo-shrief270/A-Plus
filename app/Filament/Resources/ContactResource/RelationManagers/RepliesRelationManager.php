<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    protected static ?string $title = 'الردود';

    protected static ?string $modelLabel = 'رد';
    protected static ?string $pluralModelLabel = 'الردود';

    protected static ?string $recordTitleAttribute = 'body';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_staff')
                    ->label('رد رسمي من الدعم')
                    ->default(true)
                    ->inline(false),
                Forms\Components\Textarea::make('body')
                    ->label('نص الرد')
                    ->required()
                    ->rows(6)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                // Image-only multi-upload. Stored under public disk so the
                // user-facing dashboard can render them via a CDN-style URL.
                // Held in a virtual `_attachments` key so we can persist them
                // via the relation after the reply row is saved.
                Forms\Components\FileUpload::make('_attachments')
                    ->label('صور مرفقة (اختياري)')
                    ->multiple()
                    ->image()
                    ->maxFiles(10)
                    ->maxSize(5120)
                    ->disk('public')
                    ->directory('contact_attachments/staff')
                    ->visibility('public')
                    ->reorderable()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\IconColumn::make('is_staff')
                    ->label('من')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('primary'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('الكاتب')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('body')
                    ->label('النص')
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('متى')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة رد')
                    ->icon('heroicon-o-paper-airplane')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        $data['is_staff'] = $data['is_staff'] ?? true;
                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        // Persist any uploaded images into contact_attachments,
                        // linked to this reply via the polymorphic attachable.
                        foreach (($data['_attachments'] ?? []) as $path) {
                            \App\Models\ContactAttachment::create([
                                'attachable_type' => \App\Models\ContactReply::class,
                                'attachable_id' => $record->id,
                                'path' => $path,
                                'original_name' => basename($path),
                            ]);
                        }

                        $contact = $record->contact;
                        if (!$contact) return;

                        $contact->forceFill([
                            'last_reply_at' => now(),
                            'status' => $contact->status === 'closed'
                                ? $contact->status
                                : ($record->is_staff ? 'pending' : 'open'),
                        ])->save();

                        if ($record->is_staff && $contact->user) {
                            $contact->user->notify(new \App\Notifications\SimpleNotification(
                                title: 'رد جديد على رسالتك',
                                description: ($contact->subject ?: 'رسالة تواصل') . ' — تم الرد من فريق الدعم',
                                link: '/dashboard/tickets/' . $contact->id,
                                color: 'info',
                                icon: 'i-heroicons-chat-bubble-left-right',
                            ));
                        }
                    }),
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
