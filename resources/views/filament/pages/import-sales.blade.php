<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Upload -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <x-filament::section
                icon="heroicon-o-cloud-arrow-up"
                heading="Upload File Penjualan (ESB Excel)"
                description="Silakan pilih atau tarik (drag & drop) file .xlsx, .xls, atau .csv ke area di bawah ini."
            >
                <form wire:submit="import" class="space-y-6">
                    
                    {{ $this->form }}

                    <div class="flex items-center justify-end">
                        <x-filament::button 
                            type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="import"
                            icon="heroicon-m-play"
                        >
                            <span wire:loading.remove wire:target="import">Proses File Sekarang</span>
                            <span wire:loading wire:target="import">Memproses Data...</span>
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            <!-- Hasil Import Terakhir -->
            @if($importResult)
                <x-filament::section 
                    icon="heroicon-o-check-circle" 
                    icon-color="success"
                    heading="Import Berhasil Diproses"
                    description="Seluruh baris transaksi dari file excel telah berhasil diverifikasi dan disimpan."
                >
                    <x-slot name="headerEnd">
                        <x-filament::icon-button 
                            icon="heroicon-m-x-mark" 
                            color="gray" 
                            label="Tutup"
                            wire:click="$set('importResult', null)"
                        />
                    </x-slot>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kantin</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate" title="{{ $importResult['kantin'] }}">{{ $importResult['kantin'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Periode</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $importResult['period'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Baris</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($importResult['total_rows']) }}</p>
                        </div>
                        <div class="p-4 rounded-lg bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20">
                            <p class="text-xs font-medium text-success-600 dark:text-success-400">Total Omzet</p>
                            <p class="mt-1 text-sm font-semibold text-success-600 dark:text-success-400 truncate">Rp {{ number_format($importResult['total_amount'], 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-filament::button 
                            tag="a" 
                            href="{{ route('filament.dashboard.resources.sales-details.index') }}" 
                            color="gray"
                            icon="heroicon-m-arrow-right"
                            icon-position="after"
                        >
                            Lihat Laporan Penjualan
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif
        </div>

        <!-- Ketentuan Format -->
        <div class="lg:col-span-1">
            <x-filament::section
                icon="heroicon-o-information-circle"
                icon-color="info"
                heading="Ketentuan Format File"
            >
                <div class="fi-prose prose prose-sm dark:prose-invert max-w-none">
                    <p>
                        Panduan struktur file Excel (ESB) agar bisa diproses oleh sistem:
                    </p>
                    <ol>
                        <li>
                            <strong>Header Metadata</strong><br>
                            Sistem akan mendeteksi lokasi kantin dan periode pada 10 baris pertama. Pastikan format penulisan ini ada:
                            <ul>
                                <li><code>Branch: [Nama Kantin]</code></li>
                                <li><code>Period: [Rentang Waktu]</code></li>
                            </ul>
                        </li>
                        <li>
                            <strong>Kolom Wajib Tabel</strong><br>
                            Judul kolom pada tabel wajib sama persis (huruf besar/kecil berpengaruh) dengan ini:
                            <ul>
                                <li><code>Menu Category</code></li>
                                <li><code>Item Name</code></li>
                                <li><code>Sales Qty</code></li>
                                <li><code>Grand Total</code></li>
                            </ul>
                            <em>Catatan: Nilai pada kolom <strong>Menu Category</strong> akan otomatis digunakan sistem sebagai nama Tenant.</em>
                        </li>
                        <li>
                            <strong>Anti-Duplikasi & Sinkronisasi</strong><br>
                            <ul>
                                <li>Sistem menolak import jika <strong>Kantin</strong> dan <strong>Periode</strong>-nya sudah pernah sukses diproses sebelumnya.</li>
                                <li>Kantin & Tenant yang baru (belum ada di database) akan <strong>otomatis dibuat</strong> oleh sistem.</li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </x-filament::section>
        </div>
    </div>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
