<?php

namespace App\Filament\Resources\Kantins\Pages;

use App\Filament\Resources\Kantins\KantinResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKantins extends ManageRecords
{
    protected static string $resource = KantinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
