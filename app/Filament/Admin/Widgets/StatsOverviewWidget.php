<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayDonations = Donation::where('payment_status', 'success')
            ->whereDate('created_at', today())
            ->sum('amount');

        $monthlyDonations = Donation::where('payment_status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

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

            Stat::make('Today Donations', 'Rp ' . number_format($todayDonations, 0, ',', '.'))
                ->description('Donations today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Monthly Donations', 'Rp ' . number_format($monthlyDonations, 0, ',', '.'))
                ->description('This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
