<?php

namespace App\Filament\Admin\Resources\CampaignVerificationRequestResource\Pages;

use App\Filament\Admin\Resources\CampaignVerificationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignVerificationRequests extends ListRecords
{
    protected static string $resource = CampaignVerificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
