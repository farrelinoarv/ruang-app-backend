<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge(User::count()),
            'unverified' => Tab::make('Unverified Civitas')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_verified_civitas', false))
                ->badge(User::where('is_verified_civitas', false)->count())
                ->badgeColor('warning'),
            'verified' => Tab::make('Verified Civitas')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_verified_civitas', true))
                ->badge(User::where('is_verified_civitas', true)->count())
                ->badgeColor('success'),
            'admins' => Tab::make('Admins')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('role', 'admin'))
                ->badge(User::where('role', 'admin')->count())
                ->badgeColor('primary'),
        ];
    }
}
