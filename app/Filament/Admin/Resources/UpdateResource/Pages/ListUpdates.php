<?php

namespace App\Filament\Admin\Resources\UpdateResource\Pages;

use App\Filament\Admin\Resources\UpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUpdates extends ListRecords
{
    protected static string $resource = UpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Updates are created via API by campaign owners
        ];
    }
}
