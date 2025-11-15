<?php

namespace App\Filament\Admin\Resources\CampaignResource\Pages;

use App\Filament\Admin\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Campaigns')
                ->badge(Campaign::count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(Campaign::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved'))
                ->badge(Campaign::where('status', 'approved')->count())
                ->badgeColor('success'),
            'edit_pending' => Tab::make('Edit Pending')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'edit_pending'))
                ->badge(Campaign::where('status', 'edit_pending')->count())
                ->badgeColor('info'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(Campaign::where('status', 'rejected')->count())
                ->badgeColor('danger'),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'closed'))
                ->badge(Campaign::where('status', 'closed')->count())
                ->badgeColor('gray'),
        ];
    }
}
