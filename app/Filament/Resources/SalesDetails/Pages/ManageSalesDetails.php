<?php

namespace App\Filament\Resources\SalesDetails\Pages;

use App\Filament\Pages\ImportSales;
use App\Filament\Resources\SalesDetails\SalesDetailResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageSalesDetails extends ManageRecords
{
    protected static string $resource = SalesDetailResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (auth()->user()?->isAdmin()) {
            $actions[] = Action::make('import')
                ->label('Upload File ESB')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->url(ImportSales::getUrl());
        }

        return $actions;
    }
}
