<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->label('Full Name'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->label('Email Address'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(30)
                            ->label('Phone Number'),
                    ])->columns(2),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options([
                                'user' => 'User',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('user')
                            ->label('Role'),
                        Forms\Components\Toggle::make('is_verified_civitas')
                            ->label('Civitas Verified')
                            ->helperText('Toggle to verify user as civitas member')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->label('Password')
                            ->helperText('Leave blank to keep current password when editing'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Email')
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Phone')
                    ->default('—'),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'user',
                        'success' => 'admin',
                    ])
                    ->label('Role'),
                Tables\Columns\IconColumn::make('is_verified_civitas')
                    ->boolean()
                    ->label('Civitas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Registered')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                    ])
                    ->label('Role'),
                Tables\Filters\TernaryFilter::make('is_verified_civitas')
                    ->label('Civitas Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleCivitas')
                    ->label(fn(User $record) => $record->is_verified_civitas ? 'Unverify Civitas' : 'Verify Civitas')
                    ->icon(fn(User $record) => $record->is_verified_civitas ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(User $record) => $record->is_verified_civitas ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'is_verified_civitas' => !$record->is_verified_civitas,
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
