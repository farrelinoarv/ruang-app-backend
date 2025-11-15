<?php

namespace App\Filament\Admin\Resources\CampaignVerificationRequestResource\Pages;

use App\Filament\Admin\Resources\CampaignVerificationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampaignVerificationRequest extends EditRecord
{
    protected static string $resource = CampaignVerificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
