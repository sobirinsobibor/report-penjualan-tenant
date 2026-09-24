<?php

namespace App\Filament\Resources\TargetBulanan\Pages;

use App\Filament\Resources\TargetBulanan\TargetBulananResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTargetBulanan extends ManageRecords
{
    protected static string $resource = TargetBulananResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
