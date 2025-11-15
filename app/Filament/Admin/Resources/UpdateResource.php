<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UpdateResource\Pages;
use App\Filament\Admin\Resources\UpdateResource\RelationManagers;
use App\Models\Update;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UpdateResource extends Resource
{
    protected static ?string $model = Update::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Campaign Updates';

    protected static ?string $navigationGroup = 'Campaign Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Update Information')
                    ->schema([
                        Forms\Components\Select::make('campaign_id')
                            ->relationship('campaign', 'title')
                            ->required()
                            ->searchable()
                            ->label('Campaign'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(191)
                            ->columnSpanFull()
                            ->label('Title'),
                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull()
                            ->label('Content'),
                        Forms\Components\FileUpload::make('media_path')
                            ->image()
                            ->disk('public')
                            ->directory('updates')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull()
                            ->label('Media Image'),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Posted By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\ImageColumn::make('media_path')
                    ->disk('public')
                    ->label('Media')
                    ->size(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->label('Campaign'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
            'index' => Pages\ListUpdates::route('/'),
        ];
    }
}
