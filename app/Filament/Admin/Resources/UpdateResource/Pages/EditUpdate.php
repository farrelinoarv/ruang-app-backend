<?php

namespace App\Filament\Admin\Resources\UpdateResource\Pages;

use App\Filament\Admin\Resources\UpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUpdate extends EditRecord
{
    protected static string $resource = UpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
