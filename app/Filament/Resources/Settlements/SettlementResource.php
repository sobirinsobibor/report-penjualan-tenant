<?php

namespace App\Filament\Resources\Settlements;

use App\Filament\Resources\Settlements\Pages\ManageSettlements;
use App\Models\SalesDetail;
use App\Models\Settlement;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $modelLabel = 'Settlement Bagi Hasil';

    protected static ?string $pluralModelLabel = 'Settlement Bagi Hasil';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(mixed $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['tenant.kantin']);
        $user = auth()->user();

        if ($user && !$user->isAdmin()) {
            $query->where('tenant_id', $user->tenant_id ?? 0);
        }

        return $query->latest('period_end');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tenant_id')
                ->label('Tenant')
                ->options(Tenant::pluck('name', 'id'))
                ->required()
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $tenant = Tenant::find($state);
                        if ($tenant) {
                            $set('revenue_share_percentage', $tenant->fee_percentage);
                            $set('operational_fee', $tenant->fixed_fee);
                        }
                    }
                }),
            TextInput::make('period_raw')
                ->label('Nama Periode (e.g. Periode 1–15 September 2026)')
                ->required()
                ->maxLength(255),
            DatePicker::make('period_start')
                ->label('Tanggal Mulai')
                ->required(),
            DatePicker::make('period_end')
                ->label('Tanggal Selesai')
                ->required(),
            TextInput::make('gross_sales')
                ->label('Total Omzet Penjualan (Gross/Net)')
                ->prefix('Rp')
                ->numeric()
                ->required(),
            TextInput::make('revenue_share_percentage')
                ->label('Komponen Bagi Hasil (%)')
                ->suffix('%')
                ->numeric()
                ->required(),
            TextInput::make('revenue_share_amount')
                ->label('Nominal Bagi Hasil')
                ->prefix('Rp')
                ->numeric()
                ->required(),
            TextInput::make('operational_fee')
                ->label('Biaya Operasional / Fixed Fee')
                ->prefix('Rp')
                ->numeric()
                ->default(0),
            TextInput::make('other_deductions')
                ->label('Potongan Lainnya (Listrik/Promo/dll)')
                ->prefix('Rp')
                ->numeric()
                ->default(0),
            TextInput::make('deduction_notes')
                ->label('Catatan Rincian Biaya')
                ->placeholder('e.g. Biaya Listrik Rp50.000 & Kebersihan')
                ->columnSpanFull(),
            TextInput::make('settlement_amount')
                ->label('Settlement Net (Hak Tenant)')
                ->prefix('Rp')
                ->numeric()
                ->required(),
            Select::make('status')
                ->label('Status Settlement')
                ->options([
                    'calculated' => 'Calculated (Dihitung)',
                    'approved' => 'Approved (Disetujui)',
                    'paid' => 'Paid (Telah Ditransfer)',
                ])
                ->default('calculated')
                ->required(),
            Textarea::make('notes')
                ->label('Catatan Tambahan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('period_raw')
                    ->label('Periode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gross_sales')
                    ->label('Omzet Penjualan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))),
                TextColumn::make('revenue_share_amount')
                    ->label('Bagi Hasil (%)')
                    ->formatStateUsing(fn ($state, Settlement $record) => 'Rp ' . number_format((float)$state, 0, ',', '.') . " ({$record->revenue_share_percentage}%)")
                    ->alignEnd()
                    ->color('warning'),
                TextColumn::make('total_biaya')
                    ->label('Biaya Relevan')
                    ->state(fn (Settlement $record) => (float)$record->operational_fee + (float)$record->other_deductions)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->color('danger')
                    ->tooltip(fn (Settlement $record) => $record->deduction_notes ?? 'Biaya operasional / fasilitas'),
                TextColumn::make('settlement_amount')
                    ->label('Settlement Net')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success')
                    ->summarize(Sum::make()->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'approved' => 'info',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid' => 'Ditransfer',
                        'approved' => 'Disetujui',
                        default => 'Draft',
                    }),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Filter Tenant')
                    ->options(Tenant::pluck('name', 'id'))
                    ->visible($isAdmin),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Hitung Settlement Manual')
                    ->visible($isAdmin),
                Action::make('generate_all')
                    ->label('Hitung Otomatis dari Sales Data')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->color('amber')
                    ->visible($isAdmin)
                    ->form([
                        Select::make('tenant_id')
                            ->label('Pilih Tenant')
                            ->options(Tenant::pluck('name', 'id'))
                            ->required(),
                        DatePicker::make('period_start')
                            ->label('Dari Tanggal')
                            ->required(),
                        DatePicker::make('period_end')
                            ->label('Sampai Tanggal')
                            ->required(),
                        TextInput::make('other_deductions')
                            ->label('Potongan Biaya Relevan (Listrik, Kebersihan, Promo)')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0),
                        TextInput::make('deduction_notes')
                            ->label('Rincian Potongan')
                            ->placeholder('e.g. Maintenance listrik & kebersihan'),
                    ])
                    ->action(function (array $data) {
                        $tenant = Tenant::findOrFail($data['tenant_id']);
                        $settlement = Settlement::calculateForTenant(
                            $tenant,
                            $data['period_start'],
                            $data['period_end'],
                            (float)($data['other_deductions'] ?? 0),
                            $data['deduction_notes'] ?? null
                        );
                        $settlement->created_by = auth()->id();
                        $settlement->save();
                    }),
            ])
            ->recordActions([
                EditAction::make()->visible($isAdmin),
                DeleteAction::make()->visible($isAdmin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible($isAdmin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettlements::route('/'),
        ];
    }
}
