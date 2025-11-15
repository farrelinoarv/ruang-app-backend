<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CampaignVerificationRequestResource\Pages;
use App\Filament\Admin\Resources\CampaignVerificationRequestResource\RelationManagers;
use App\Models\CampaignVerificationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignVerificationRequestResource extends Resource
{
    protected static ?string $model = CampaignVerificationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Civitas Verification';

    protected static ?string $navigationGroup = 'Campaign Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->required(),
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('identity_type')
                    ->required(),
                Forms\Components\TextInput::make('identity_number')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('proof_file')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('organization_name')
                    ->maxLength(191),
                Forms\Components\TextInput::make('verification_status')
                    ->required(),
                Forms\Components\TextInput::make('reviewed_by')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('reviewed_at'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('identity_type')
                    ->label('ID Type')
                    ->colors([
                        'primary' => 'KTM',
                        'info' => 'KTP',
                        'success' => 'Surat Tugas',
                    ]),
                Tables\Columns\TextColumn::make('identity_number')
                    ->label('ID Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(CampaignVerificationRequest $record) => $record->verification_status === 'pending')
                    ->action(function (CampaignVerificationRequest $record) {
                        $record->update([
                            'verification_status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Rejection Notes')
                            ->required(),
                    ])
                    ->visible(fn(CampaignVerificationRequest $record) => $record->verification_status === 'pending')
                    ->action(function (CampaignVerificationRequest $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'notes' => $data['notes'],
                        ]);
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
            'index' => Pages\ListCampaignVerificationRequests::route('/'),
            'create' => Pages\CreateCampaignVerificationRequest::route('/create'),
            'edit' => Pages\EditCampaignVerificationRequest::route('/{record}/edit'),
        ];
    }
}
