<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Campaign;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Verified Civitas', User::where('is_verified_civitas', true)->count())
                ->description('Verified members')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Active Campaigns', Campaign::where('status', 'approved')->count())
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('info'),

            Stat::make('Pending Campaigns', Campaign::where('status', 'pending')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
