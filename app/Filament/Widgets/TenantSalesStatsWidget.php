<?php

namespace App\Filament\Widgets;

use App\Models\SalesDetail;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TenantSalesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isTenant();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $tenantId = $user?->tenant_id;
        $tenant = $user?->tenant;

        if (!$tenantId) {
            return [
                Stat::make('Status Tenant', 'Belum Terhubung')
                    ->description('Akun ini belum ditautkan ke profil Tenant manapun oleh Admin')
                    ->color('danger'),
            ];
        }

        $totalRevenue = SalesDetail::where('tenant_id', $tenantId)->sum('grand_total');
        $totalQty = SalesDetail::where('tenant_id', $tenantId)->sum('qty');

        $topItem = SalesDetail::where('tenant_id', $tenantId)
            ->select('item_name', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->first();

        $topItemName = $topItem ? "{$topItem->item_name} (" . number_format($topItem->total_qty) . " terjual)" : '-';

        return [
            Stat::make('Total Pendapatan Anda', 'Rp ' . number_format((float)$totalRevenue, 0, ',', '.'))
                ->description($tenant ? "Tenant: {$tenant->name} ({$tenant->kantin?->name})" : '')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total Produk Terjual', number_format((float)$totalQty, 0, ',', '.') . ' Qty')
                ->description('Seluruh periode penjualan')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('Menu Paling Laris', $topItemName)
                ->description('Berdasarkan kuantitas terjual')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),
        ];
    }
}
