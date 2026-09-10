<?php

namespace App\Filament\Pages;

use App\Models\SalesImport;
use App\Services\EsbImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\WithFileUploads;
use Throwable;
use UnitEnum;

class ImportSales extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Import Data Penjualan';

    protected static ?string $title = 'Import Data Penjualan (ESB Excel)';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.import-sales';

    public $file;
    public ?array $importResult = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAbility('sales.import') ?? false;
    }

    public function import(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480',
        ], [
            'file.required' => 'Silakan pilih file Excel / CSV terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            $service = app(EsbImportService::class);
            $path = $this->file->getRealPath();
            $originalName = $this->file->getClientOriginalName();

            $result = $service->import($path, $originalName, auth()->id());
            $this->importResult = $result;

            Notification::make()
                ->title('Import Berhasil!')
                ->body("Kantin: {$result['kantin']} | Periode: {$result['period']} | {$result['total_rows']} baris berhasil disimpan.")
                ->success()
                ->duration(10000)
                ->send();

            $this->reset('file');
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

    public function getViewData(): array
    {
        return [
            'recentImports' => SalesImport::with(['kantin', 'uploader'])
                ->latest()
                ->take(15)
                ->get(),
        ];
    }
}
