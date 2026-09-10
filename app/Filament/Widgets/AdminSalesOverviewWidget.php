<?php

namespace App\Filament\Widgets;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminSalesOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $totalRevenue = SalesDetail::sum('grand_total');
        $totalQty = SalesDetail::sum('qty');
        $kantinCount = Kantin::count();
        $tenantCount = Tenant::count();
        $importCount = SalesImport::count();

        return [
            Stat::make('Total Penjualan Global', 'Rp ' . number_format((float)$totalRevenue, 0, ',', '.'))
                ->description("Dari {$importCount} batch import")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Item Terjual', number_format((float)$totalQty, 0, ',', '.') . ' Qty')
                ->description('Akumulasi seluruh menu')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            Stat::make('Total Kantin & Tenant', "{$kantinCount} Kantin / {$tenantCount} Tenant")
                ->description('Terdaftar di sistem')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
        ];
    }
}
