<?php

namespace App\Filament\Resources\SalesDetails;

use App\Filament\Resources\SalesDetails\Pages\ManageSalesDetails;
use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SalesDetailResource extends Resource
{
    protected static ?string $model = SalesDetail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $modelLabel = 'Laporan Penjualan';

    protected static ?string $pluralModelLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasAbility('sales.view_all') || $user->hasAbility('sales.view_own'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->hasAbility('sales.delete') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['salesImport.kantin', 'tenant'])
            ->join('sales_imports', 'sales_details.sales_import_id', '=', 'sales_imports.id')
            ->join('kantins', 'sales_imports.kantin_id', '=', 'kantins.id')
            ->leftJoin('tenants', 'sales_details.tenant_id', '=', 'tenants.id')
            ->orderBy('kantins.name')
            ->orderBy('tenants.name')
            ->orderByDesc('sales_details.id')
            ->selectRaw("sales_details.*, CONCAT(kantins.name, ' → ', COALESCE(tenants.name, 'Unknown')) as kantin_tenant");

        $user = auth()->user();
        if ($user && !$user->hasAbility('sales.view_all')) {
            $query->where('sales_details.tenant_id', $user->tenant_id ?? 0);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $canViewAll = auth()->user()?->hasAbility('sales.view_all') ?? false;
        $canDelete = auth()->user()?->hasAbility('sales.delete') ?? false;

        return $table
            ->groups([
                Group::make('kantin_tenant')
                    ->label('Group')
                    ->collapsible()
                    ->orderQueryUsing(fn (Builder $query, string $direction) => 
                        $query
                            ->orderBy('kantins.name', $direction)
                            ->orderBy('tenants.name', $direction)
                            ->orderByDesc('sales_details.id')
                    )
                    ->scopeQueryUsing(function (Builder $query, SalesDetail $record) {
                        $kantinId = $record->salesImport?->kantin_id ?? $record->salesImport()->value('kantin_id');

                        return $query
                            ->when(
                                $kantinId,
                                fn (Builder $q) => $q->where('sales_imports.kantin_id', $kantinId)
                            )
                            ->when(
                                $record->tenant_id === null,
                                fn (Builder $q) => $q->whereNull('sales_details.tenant_id'),
                                fn (Builder $q) => $q->where('sales_details.tenant_id', $record->tenant_id)
                            );
                    })
                    ->scopeQueryByKeyUsing(function (Builder $query, ?string $key) {
                        if (blank($key)) {
                            return $query;
                        }

                        return $query->whereRaw("CONCAT(kantins.name, ' → ', COALESCE(tenants.name, 'Unknown')) = ?", [$key]);
                    }),
                Group::make('salesImport.kantin.name')
                    ->label('Kantin')
                    ->collapsible(),
                Group::make('tenant.name')
                    ->label('Tenant')
                    ->collapsible(),
            ])
            ->defaultGroup('kantin_tenant')
            ->columns([
                TextColumn::make('salesImport.kantin.name')
                    ->label('Kantin')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->visible($canViewAll),
                TextColumn::make('salesImport.period_raw')
                    ->label('Periode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant (Kategori)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible($canViewAll),
                TextColumn::make('item_name')
                    ->label('Menu / Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Qty Terjual')
                    ->numeric(decimalPlaces: 0)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Qty')
                            ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ),
                TextColumn::make('gross_sales')
                    ->label('Gross Sales')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount')
                    ->label('Diskon')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('grand_total')
                    ->label('Grand Total (Net)')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Sum::make()
                            ->label('Total Omzet')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ),
                TextColumn::make('tenant.fee_percentage')
                    ->label('Fee (%)')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2) . '%')
                    ->alignEnd()
                    ->color('warning'),
                TextColumn::make('fee_amount')
                    ->label('Potongan Fee')
                    ->state(function (SalesDetail $record): float {
                        $fee = $record->tenant?->fee_percentage ?? 0;
                        return (float)$record->grand_total * (float)$fee / 100;
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('after_fee')
                    ->label('Setelah Fee')
                    ->state(function (SalesDetail $record): float {
                        $fee = $record->tenant?->fee_percentage ?? 0;
                        return (float)$record->grand_total * (1 - (float)$fee / 100);
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label('Waktu Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Filter Kantin')
                    ->options(fn () => Kantin::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('salesImport', function (Builder $q) use ($data) {
                                $q->where('kantin_id', $data['value']);
                            });
                        }
                    })
                    ->visible($canViewAll),
                SelectFilter::make('tenant_id')
                    ->label('Filter Tenant')
                    ->options(fn () => Tenant::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->visible($canViewAll),
                Filter::make('period_date')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date) => $q->whereHas('salesImport', fn (Builder $sq) => $sq->whereDate('period_start', '>=', $date))
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date) => $q->whereHas('salesImport', fn (Builder $sq) => $sq->whereDate('period_end', '<=', $date))
                            );
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->visible($canDelete),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible($canDelete),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalesDetails::route('/'),
        ];
    }
}
