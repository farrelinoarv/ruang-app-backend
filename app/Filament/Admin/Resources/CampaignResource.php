<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CampaignResource\Pages;
use App\Filament\Admin\Resources\CampaignResource\RelationManagers;
use App\Models\Campaign;
use App\Services\CampaignService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $navigationGroup = 'Campaign Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Campaign Owner'),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Category'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(191)
                            ->columnSpanFull()
                            ->label('Title'),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull()
                            ->label('Description'),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('target_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Target Amount'),
                        Forms\Components\TextInput::make('collected_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->label('Collected Amount'),
                        Forms\Components\DatePicker::make('deadline')
                            ->required()
                            ->label('Deadline')
                            ->minDate(now()),
                    ])->columns(3),

                Forms\Components\Section::make('Status & Media')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'edit_pending' => 'Edit Pending',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('pending')
                            ->label('Status'),
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->disk('public')
                            ->directory('campaigns')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->label('Cover Image'),
                    ])->columns(2),

                Forms\Components\Section::make('Pending Changes')
                    ->schema([
                        Forms\Components\Placeholder::make('pending_changes_info')
                            ->content(fn($record) => $record && $record->pending_changes
                                ? json_encode($record->pending_changes, JSON_PRETTY_PRINT)
                                : 'No pending changes')
                            ->label('Pending Edit Requests'),
                    ])
                    ->visible(fn($record) => $record && $record->status === 'edit_pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold')
                    ->label('Title'),
                Tables\Columns\ImageColumn::make('cover_image')
                    ->disk('public')
                    ->label('Cover')
                    ->size(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Owner'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('target_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Target'),
                Tables\Columns\TextColumn::make('collected_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Collected'),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                    ->color(fn($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'edit_pending',
                        'gray' => 'closed',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Deadline'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'edit_pending' => 'Edit Pending',
                        'closed' => 'Closed',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Approve Campaign
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Campaign $record) => $record->status === 'pending')
                    ->action(function (Campaign $record) {
                        $campaignService = app(CampaignService::class);
                        $admin = auth()->user();
                        $campaignService->approveCampaign($record, $admin);

                        Notification::make()
                            ->success()
                            ->title('Campaign Approved')
                            ->body('Campaign has been approved successfully.')
                            ->send();
                    }),

                // Reject Campaign
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_notes')
                            ->label('Rejection Notes')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn(Campaign $record) => $record->status === 'pending')
                    ->action(function (Campaign $record, array $data) {
                        $campaignService = app(CampaignService::class);
                        $admin = auth()->user();
                        $campaignService->rejectCampaign($record, $admin, $data['rejection_notes']);

                        Notification::make()
                            ->success()
                            ->title('Campaign Rejected')
                            ->body('Campaign has been rejected.')
                            ->send();
                    }),

                // Approve Edit
                Tables\Actions\Action::make('approveEdit')
                    ->label('Approve Edit')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Campaign $record) => $record->status === 'edit_pending')
                    ->action(function (Campaign $record) {
                        if ($record->pending_changes) {
                            foreach ($record->pending_changes as $key => $value) {
                                $record->$key = $value;
                            }
                            $record->pending_changes = null;
                            $record->status = 'approved';
                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('Edit Approved')
                                ->body('Campaign changes have been approved.')
                                ->send();
                        }
                    }),

                // Reject Edit
                Tables\Actions\Action::make('rejectEdit')
                    ->label('Reject Edit')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Campaign $record) => $record->status === 'edit_pending')
                    ->action(function (Campaign $record) {
                        $record->pending_changes = null;
                        $record->status = 'approved';
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Edit Rejected')
                            ->body('Campaign changes have been rejected. Campaign returned to approved status.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
