<?php

namespace App\Filament\Widgets;

use App\Models\SalesDetail;
use App\Models\SalesImport;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantSalesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isTenant() || $user->isAdmin());
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $tenantId = $user?->tenant_id;
        $tenant = $user?->tenant;

        // If admin viewing overview without specific tenant link
        if ($user->isAdmin() && !$tenantId) {
            $totalRevenue = (float) SalesDetail::sum('grand_total');
            $totalQty = (float) SalesDetail::sum('qty');
            $totalTx = SalesDetail::count();
            $avgTx = $totalTx > 0 ? $totalRevenue / $totalTx : 0;

            // Global month revenue & target
            $startOfMonthStr = now()->startOfMonth()->format('Y-m-d');
            $endOfMonthStr = now()->endOfMonth()->format('Y-m-d');

            $monthRevenue = (float) SalesDetail::whereHas('salesImport', function ($q) use ($startOfMonthStr, $endOfMonthStr) {
                $q->whereDate('period_end', '>=', $startOfMonthStr)
                  ->whereDate('period_start', '<=', $endOfMonthStr);
            })->sum('grand_total');

            if ($monthRevenue == 0) {
                $monthRevenue = $totalRevenue;
            }

            $totalTarget = (float) \App\Models\Tenant::sum('target_omzet');
            if ($totalTarget == 0) {
                $totalTarget = \App\Models\Tenant::count() * 55000000;
            }
            $globalAchievement = $totalTarget > 0 ? ($monthRevenue / $totalTarget) * 100 : 0;

            return [
                Stat::make('Omzet Hari Ini (Global)', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                    ->description('Seluruh tenant foodcourt')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success'),
                Stat::make('Total Transaksi', number_format($totalTx) . ' Transaksi')
                    ->description('Average Tx: Rp ' . number_format($avgTx, 0, ',', '.'))
                    ->descriptionIcon('heroicon-m-shopping-cart')
                    ->color('primary'),
                Stat::make('Omzet Bulan Ini', 'Rp ' . number_format($monthRevenue, 0, ',', '.'))
                    ->description('Bulan Berjalan')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('info'),
                Stat::make('Target Bulanan Global', 'Rp ' . number_format($totalTarget, 0, ',', '.'))
                    ->description('Akumulasi seluruh tenant')
                    ->descriptionIcon('heroicon-m-flag')
                    ->color('secondary'),
                Stat::make('Achievement Target', number_format($globalAchievement, 1, ',', '.') . '%')
                    ->description($globalAchievement >= 100 ? 'Target Global Tercapai 🎉' : 'Pencapaian Omzet Global')
                    ->descriptionIcon($globalAchievement >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-chart-bar')
                    ->color($globalAchievement >= 100 ? 'success' : ($globalAchievement >= 75 ? 'warning' : 'danger')),
            ];
        }

        if (!$tenantId) {
            return [
                Stat::make('Status Tenant', 'Belum Terhubung')
                    ->description('Akun ini belum ditautkan ke profil Tenant manapun oleh Admin')
                    ->color('danger'),
            ];
        }

        // Get latest import date for tenant data to handle pilot data cleanly
        $latestImportDate = SalesImport::whereHas('salesDetails', fn ($q) => $q->where('tenant_id', $tenantId))
            ->max('period_end');

        $refDate = $latestImportDate ? Carbon::parse($latestImportDate) : now();
        $todayStr = $refDate->format('Y-m-d');
        $yesterdayStr = $refDate->copy()->subDay()->format('Y-m-d');
        $startOfMonthStr = $refDate->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonthStr = $refDate->copy()->endOfMonth()->format('Y-m-d');

        // Today's Sales
        $todayRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
            ->whereHas('salesImport', fn ($q) => $q->whereDate('period_start', '<=', $todayStr)->whereDate('period_end', '>=', $todayStr))
            ->sum('grand_total');

        if ($todayRevenue == 0) {
            $todayRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
                ->whereHas('salesImport', fn ($q) => $q->whereDate('period_end', $todayStr))
                ->sum('grand_total');
        }

        // Yesterday's Sales
        $yesterdayRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
            ->whereHas('salesImport', fn ($q) => $q->whereDate('period_start', '<=', $yesterdayStr)->whereDate('period_end', '>=', $yesterdayStr))
            ->sum('grand_total');

        // Today's Transactions & Average Transaction
        $todayTxCount = (int) SalesDetail::where('tenant_id', $tenantId)
            ->whereHas('salesImport', fn ($q) => $q->whereDate('period_start', '<=', $todayStr)->whereDate('period_end', '>=', $todayStr))
            ->sum('qty');

        if ($todayTxCount == 0) {
            $todayTxCount = (int) SalesDetail::where('tenant_id', $tenantId)->sum('qty');
            if ($todayRevenue == 0) {
                $todayRevenue = (float) SalesDetail::where('tenant_id', $tenantId)->sum('grand_total');
            }
        }

        $avgTx = $todayTxCount > 0 ? $todayRevenue / $todayTxCount : 0;

        // Current Month's Sales
        $monthRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
            ->whereHas('salesImport', fn ($q) => $q->whereDate('period_end', '>=', $startOfMonthStr)->whereDate('period_start', '<=', $endOfMonthStr))
            ->sum('grand_total');

        if ($monthRevenue == 0) {
            $monthRevenue = (float) SalesDetail::where('tenant_id', $tenantId)->sum('grand_total');
        }

        // Monthly Target & Achievement
        $targetOmzet = (float) ($tenant?->target_omzet ?? 55000000);
        $achievement = $targetOmzet > 0 ? ($monthRevenue / $targetOmzet) * 100 : 0;

        return [
            Stat::make('Omzet Hari Ini', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description('Omzet Kemarin: Rp ' . number_format($yesterdayRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Transaksi & Avg. Tx', number_format($todayTxCount, 0, ',', '.') . ' Transaksi')
                ->description('Average Tx: Rp ' . number_format($avgTx, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('Omzet Bulan Ini', 'Rp ' . number_format($monthRevenue, 0, ',', '.'))
                ->description('Bulan Berjalan')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            Stat::make('Target Bulanan', 'Rp ' . number_format($targetOmzet, 0, ',', '.'))
                ->description('Target Omzet Tenant')
                ->descriptionIcon('heroicon-m-flag')
                ->color('secondary'),
            Stat::make('Achievement Target', number_format($achievement, 1, ',', '.') . '%')
                ->description($achievement >= 100 ? 'Target tercapai 🎉' : 'Menuju target bulanan')
                ->descriptionIcon($achievement >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-chart-bar')
                ->color($achievement >= 100 ? 'success' : ($achievement >= 75 ? 'warning' : 'danger')),
        ];
    }
}
