<?php

namespace App\Filament\Resources\TargetBulanan;

use App\Filament\Resources\TargetBulanan\Pages\ManageTargetBulanan;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TargetBulananResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan & Target';

    protected static ?string $modelLabel = 'Target Bulanan';

    protected static ?string $pluralModelLabel = 'Target Bulanan Tenant';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isTenant() || $user->hasAbility('target.view'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTenant() && $user->tenant_id === $record->id) {
            return true;
        }

        return $user->hasAbility('target.update');
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->isTenant() && $user->tenant_id) {
            $query->where('id', $user->tenant_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Tenant')
                    ->disabled(),
                TextInput::make('target_omzet')
                    ->label('Target Omzet Bulanan')
                    ->prefix('Rp')
                    ->numeric()
                    ->required()
                    ->helperText('Target omzet penjualan bulanan untuk tenant ini'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kantin.name')
                    ->label('Kantin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('target_omzet')
                    ->label('Target Bulanan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('month_revenue')
                    ->label('Omzet Bulan Ini')
                    ->state(function (Tenant $record) {
                        return static::calculateMonthRevenue($record->id);
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('achievement')
                    ->label('Achievement (%)')
                    ->state(function (Tenant $record) {
                        $revenue = static::calculateMonthRevenue($record->id);
                        $target = (float) ($record->target_omzet ?? 55000000);
                        return $target > 0 ? ($revenue / $target) * 100 : 0;
                    })
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1, ',', '.') . '%')
                    ->badge()
                    ->color(function ($state) {
                        $val = (float) $state;
                        if ($val >= 100) return 'success';
                        if ($val >= 75) return 'warning';
                        return 'danger';
                    }),
                TextColumn::make('status')
                    ->label('Status Pencapaian')
                    ->state(function (Tenant $record) {
                        $revenue = static::calculateMonthRevenue($record->id);
                        $target = (float) ($record->target_omzet ?? 55000000);
                        $achievement = $target > 0 ? ($revenue / $target) * 100 : 0;

                        if ($achievement >= 100) {
                            return 'Target Tercapai 🎉';
                        }
                        if ($achievement >= 75) {
                            return 'Mendekati Target 📈';
                        }
                        return 'Perlu Ditingkatkan ⚠️';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (str_contains($state, 'Tercapai')) return 'success';
                        if (str_contains($state, 'Mendekati')) return 'warning';
                        return 'danger';
                    }),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Filter Kantin')
                    ->relationship('kantin', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah Target')
                    ->modalHeading('Edit Target Bulanan Tenant'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTargetBulanan::route('/'),
        ];
    }

    public static function calculateMonthRevenue(int $tenantId): float
    {
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $monthRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
            ->whereHas('salesImport', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereDate('period_end', '>=', $startOfMonth)
                  ->whereDate('period_start', '<=', $endOfMonth);
            })
            ->sum('grand_total');

        if ($monthRevenue == 0) {
            $latestImportDate = SalesImport::whereHas('salesDetails', fn ($q) => $q->where('tenant_id', $tenantId))
                ->max('period_end');

            if ($latestImportDate) {
                $refDate = Carbon::parse($latestImportDate);
                $s = $refDate->copy()->startOfMonth()->format('Y-m-d');
                $e = $refDate->copy()->endOfMonth()->format('Y-m-d');

                $monthRevenue = (float) SalesDetail::where('tenant_id', $tenantId)
                    ->whereHas('salesImport', fn ($q) => $q->whereDate('period_end', '>=', $s)->whereDate('period_start', '<=', $e))
                    ->sum('grand_total');
            }
        }

        if ($monthRevenue == 0) {
            $monthRevenue = (float) SalesDetail::where('tenant_id', $tenantId)->sum('grand_total');
        }

        return $monthRevenue;
    }
}
