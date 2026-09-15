<?php

namespace App\Filament\Pages;

use App\Models\SalesImport;
use App\Services\EsbImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;
use UnitEnum;

class ImportSales extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Import Data Penjualan';

    protected static ?string $title = 'Import Data Penjualan (ESB Excel)';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.import-sales';

    public ?array $data = [];
    public ?array $importResult = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAbility('sales.import') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_reports')
                ->label('Lihat Laporan Penjualan')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('filament.dashboard.resources.sales-details.index')),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Pilih File (Excel / CSV)')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv'
                    ])
                    ->maxSize(20480)
                    ->storeFiles(false)
                    ->required()
                    ->helperText('Maksimal 20 MB (.xlsx, .xls, .csv)')
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();
        
        /** @var TemporaryUploadedFile $uploadedFile */
        $uploadedFile = $data['file'];

        try {
            $service = app(EsbImportService::class);
            $path = $uploadedFile->getRealPath();
            $originalName = $uploadedFile->getClientOriginalName();

            $result = $service->import($path, $originalName, auth()->id());
            $this->importResult = $result;

            Notification::make()
                ->title('Import Berhasil!')
                ->body("Kantin: {$result['kantin']} | Periode: {$result['period']} | {$result['total_rows']} baris berhasil disimpan.")
                ->success()
                ->duration(10000)
                ->send();

            $this->form->fill(); // Reset form
            $this->resetTable();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal Mengimpor File')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function deleteImport(int $importId): void
    {
        if (!auth()->user()?->isAdmin()) {
            return;
        }

        $import = SalesImport::findOrFail($importId);
        $kantinName = $import->kantin?->name;
        $period = $import->period_raw;
        $import->delete();

        Notification::make()
            ->title('Data Import Dihapus')
            ->body("Batch import {$kantinName} ({$period}) beserta rinciannya telah dihapus.")
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $table
            ->query(SalesImport::query()->with(['kantin', 'uploader', 'salesDetails.tenant'])->latest())
            ->heading('Riwayat Batch Import')
            ->description('Daftar file rekapitulasi penjualan ESB yang telah berhasil diimpor ke sistem.')
            ->columns([
                TextColumn::make('kantin.name')
                    ->label('Kantin')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_raw')
                    ->label('Periode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_rows')
                    ->label('Baris Data')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Omzet')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('tenant_fees')
                    ->label('Tenant & Fee')
                    ->state(function (SalesImport $record): string {
                        return $record->salesDetails->groupBy('tenant_id')->map(function ($details) {
                            $tenant = $details->first()->tenant;
                            $name = $tenant?->name ?? 'Unknown';
                            $fee = $tenant ? number_format((float)$tenant->fee_percentage, 2) : '0.00';
                            $subtotal = $details->sum('grand_total');
                            $feeAmount = $subtotal * (float)($tenant?->fee_percentage ?? 0) / 100;
                            return "{$name} ({$fee}%) = Rp " . number_format($feeAmount, 0, ',', '.');
                        })->implode("\n");
                    })
                    ->wrap()
                    ->lineClamp(3)
                    ->tooltip(function (SalesImport $record): string {
                        return $record->salesDetails->groupBy('tenant_id')->map(function ($details) {
                            $tenant = $details->first()->tenant;
                            $name = $tenant?->name ?? 'Unknown';
                            $fee = number_format((float)($tenant?->fee_percentage ?? 0), 2);
                            $subtotal = $details->sum('grand_total');
                            $feeAmount = $subtotal * (float)($tenant?->fee_percentage ?? 0) / 100;
                            return "{$name}: Fee {$fee}% = Rp " . number_format($feeAmount, 0, ',', '.');
                        })->implode(' | ');
                    }),
                TextColumn::make('total_fee')
                    ->label('Total Potongan Fee')
                    ->state(function (SalesImport $record): float {
                        return $record->salesDetails->groupBy('tenant_id')->sum(function ($details) {
                            $tenant = $details->first()->tenant;
                            $fee = $tenant ? (float)$tenant->fee_percentage : 0;
                            $subtotal = $details->sum('grand_total');
                            return $subtotal * $fee / 100;
                        });
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('total_after_fee')
                    ->label('Total Setelah Fee')
                    ->state(function (SalesImport $record): float {
                        return $record->salesDetails->groupBy('tenant_id')->sum(function ($details) {
                            $tenant = $details->first()->tenant;
                            $fee = $tenant ? (float)$tenant->fee_percentage : 0;
                            $subtotal = $details->sum('grand_total');
                            return $subtotal * (1 - $fee / 100);
                        });
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('file_name')
                    ->label('Nama File')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->file_name)
                    ->searchable(),
                TextColumn::make('uploader.name')
                    ->label('Diupload Oleh')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Waktu Import')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                DeleteAction::make('delete')
                    ->label('Hapus')
                    ->icon(Heroicon::OutlinedTrash)
                    ->visible($isAdmin)
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Data Batch Import')
                    ->modalDescription(fn (SalesImport $record) => "Yakin ingin menghapus batch import {$record->kantin?->name} ({$record->period_raw})? Seluruh data rincian penjualan pada batch ini akan dihapus permanen.")
                    ->modalSubmitActionLabel('Ya, Hapus Data')
                    ->action(fn (SalesImport $record) => $this->deleteImport($record->id)),
            ])
            ->emptyStateHeading('Belum Ada Riwayat Import')
            ->emptyStateDescription('File sales report yang diunggah akan otomatis tercatat dan tersusun rapi di sini.')
            ->emptyStateIcon(Heroicon::OutlinedArrowUpTray)
            ->defaultPaginationPageOption(10);
    }
}
