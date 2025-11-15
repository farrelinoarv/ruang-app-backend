<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DonationResource\Pages;
use App\Models\Campaign;
use App\Models\Donation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Donations';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Donation Details')
                    ->schema([
                        Forms\Components\Select::make('campaign_id')
                            ->relationship('campaign', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Campaign'),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label('User (Donor)'),
                        Forms\Components\TextInput::make('donor_name')
                            ->maxLength(191)
                            ->label('Donor Name'),
                        Forms\Components\Toggle::make('is_anonymous')
                            ->label('Anonymous Donation')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Amount'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'success' => 'Success',
                                'failed' => 'Failed',
                                'expired' => 'Expired',
                            ])
                            ->required()
                            ->label('Payment Status'),
                        Forms\Components\TextInput::make('payment_method')
                            ->maxLength(50)
                            ->label('Payment Method'),
                        Forms\Components\TextInput::make('midtrans_order_id')
                            ->maxLength(191)
                            ->label('Midtrans Order ID'),
                        Forms\Components\TextInput::make('midtrans_transaction_id')
                            ->maxLength(191)
                            ->label('Midtrans Transaction ID'),
                        Forms\Components\TextInput::make('transaction_ref')
                            ->maxLength(191)
                            ->label('Transaction Reference'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Message from Donor'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('donor_name')
                    ->label('Donor')
                    ->searchable()
                    ->formatStateUsing(fn(Donation $record) => $record->display_name),
                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                        'secondary' => 'expired',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_anonymous')
                    ->label('Anonymous')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('campaign_id')
                    ->label('Campaign')
                    ->relationship('campaign', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsSuccess')
                    ->label('Mark as Success')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Donation $record) => $record->payment_status !== 'success')
                    ->action(function (Donation $record) {
                        $record->update(['payment_status' => 'success']);

                        // Update campaign collected amount
                        $campaign = $record->campaign;
                        $campaign->collected_amount = Donation::where('campaign_id', $campaign->id)
                            ->where('payment_status', 'success')
                            ->sum('amount');
                        $campaign->save();

                        // Update user wallet if exists
                        if ($record->user) {
                            $record->user->wallet()->increment('total_donated', $record->amount);
                        }

                        Notification::make()
                            ->title('Donation marked as successful')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('markAsFailed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Donation $record) => in_array($record->payment_status, ['pending', 'success']))
                    ->action(function (Donation $record) {
                        $oldStatus = $record->payment_status;
                        $record->update(['payment_status' => 'failed']);

                        // If was success, rollback campaign and wallet
                        if ($oldStatus === 'success') {
                            $campaign = $record->campaign;
                            $campaign->collected_amount = Donation::where('campaign_id', $campaign->id)
                                ->where('payment_status', 'success')
                                ->sum('amount');
                            $campaign->save();

                            if ($record->user) {
                                $record->user->wallet()->decrement('total_donated', $record->amount);
                            }
                        }

                        Notification::make()
                            ->title('Donation marked as failed')
                            ->warning()
                            ->send();
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'create' => Pages\CreateDonation::route('/create'),
            'view' => Pages\ViewDonation::route('/{record}'),
            'edit' => Pages\EditDonation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('payment_status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
