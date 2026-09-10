<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form Upload -->
        <div class="md:col-span-2 space-y-6">
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="w-5 h-5 text-primary-500" />
                    Upload File Sales Recapitulation Report (.xlsx / .csv)
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Unggah file hasil export sistem ESB. Sistem akan otomatis mendeteksi Kantin dan Periode, memeriksa duplikasi, serta mencocokkan data tenant.
                </p>

                <form wire:submit.prevent="import" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pilih File Excel / CSV
                        </label>
                        <input 
                            type="file" 
                            wire:model="file" 
                            accept=".xlsx,.xls,.csv"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-850 dark:border-gray-700 p-2.5"
                        />
                        @error('file')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="file" class="mt-2 text-xs text-primary-600 dark:text-primary-400 font-medium">
                            Mengunggah file ke browser... mohon tunggu...
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="import,file">
                            <span wire:loading.remove wire:target="import">Mulai Proses Import</span>
                            <span wire:loading wire:target="import">Memproses Data Excel...</span>
                        </x-filament::button>

                        <a href="{{ route('filament.dashboard.resources.sales-details.index') }}" class="text-sm text-primary-600 hover:underline">
                            Lihat Laporan Penjualan &rarr;
                        </a>
                    </div>
                </form>
            </div>

            @if($importResult)
                <div class="p-6 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                    <div class="flex items-center gap-3 text-emerald-800 dark:text-emerald-300 font-semibold mb-3">
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6" />
                        Hasil Import Terakhir Berhasil
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div class="bg-white/70 dark:bg-gray-900/70 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900">
                            <span class="text-xs text-gray-500 block">Kantin</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $importResult['kantin'] }}</span>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-900/70 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900">
                            <span class="text-xs text-gray-500 block">Periode</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $importResult['period'] }}</span>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-900/70 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900">
                            <span class="text-xs text-gray-500 block">Total Baris</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($importResult['total_rows']) }} item</span>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-900/70 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900">
                            <span class="text-xs text-gray-500 block">Total Omzet</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($importResult['total_amount'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Info & Petunjuk Format ESB -->
        <div class="space-y-4">
            <div class="p-6 bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-800 rounded-xl">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-blue-500" />
                    Ketentuan Format File ESB
                </h4>
                <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-2 list-disc list-inside">
                    <li><strong>Metadata Header:</strong> Berisi informasi <code>Branch: [Nama Kantin]</code> dan <code>Period: [Rentang Tanggal]</code> pada 10 baris pertama.</li>
                    <li><strong>Validasi Anti-Duplikasi:</strong> Jika kombinasi Kantin & Periode yang sama diupload ulang, sistem otomatis menolaknya.</li>
                    <li><strong>Kolom Tabel:</strong> Harus memuat minimal kolom:
                        <ul class="pl-4 pt-1 space-y-1 list-circle">
                            <li><code>Menu Category</code> (Nama Tenant)</li>
                            <li><code>Item Name</code> (Nama Produk/Menu)</li>
                            <li><code>Sales Qty</code> (Kuantitas)</li>
                            <li><code>Grand Total</code> (Total Penjualan)</li>
                        </ul>
                    </li>
                    <li><strong>Auto-Creation:</strong> Kantin & Tenant baru akan dibuat otomatis saat import jika belum terdaftar di database.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Riwayat Import -->
    <div class="mt-8">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-gray-500" />
            Riwayat Batch Import Terakhir
        </h3>

        <div class="overflow-x-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-xs text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-4 py-3">Kantin (Branch)</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-right">Baris Data</th>
                        <th class="px-4 py-3 text-right">Total Pendapatan</th>
                        <th class="px-4 py-3">Nama File</th>
                        <th class="px-4 py-3">Diupload Oleh</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($recentImports as $import)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                {{ $import->kantin?->name }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800">
                                    {{ $import->period_raw }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ number_format($import->total_rows) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($import->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 truncate max-w-xs" title="{{ $import->file_name }}">
                                {{ $import->file_name }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $import->uploader?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $import->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="deleteImport({{ $import->id }})"
                                    wire:confirm="Yakin ingin menghapus seluruh data penjualan dari batch import ini? Tindakan ini tidak dapat dibatalkan."
                                    class="text-xs text-red-600 hover:text-red-800 dark:hover:text-red-400 font-medium cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">
                                Belum ada data import penjualan yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
