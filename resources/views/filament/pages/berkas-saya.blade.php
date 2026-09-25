<x-filament-panels::page>
    @php
        $user = auth()->user();
        $tenant = $user?->tenant;
        $isComplete = $tenant?->isDocumentComplete() ?? false;
    @endphp

    <div class="mb-6 p-4 rounded-xl shadow-sm border {{ $isComplete ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg {{ $isComplete ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white' }}">
                @if($isComplete)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:24px;height:24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:24px;height:24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                @endif
            </div>
            <div>
                <h3 class="font-bold text-base">
                    Status Kelengkapan Berkas: 
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isComplete ? 'bg-emerald-200 text-emerald-900' : 'bg-amber-200 text-amber-900' }}">
                        {{ $isComplete ? 'LENGKAP' : 'BELUM LENGKAP' }}
                    </span>
                </h3>
                <p class="text-sm opacity-90">
                    @if($isComplete)
                        Seluruh berkas identitas dan foto dokumen (KTP, KK, NPWP, Sertifikat Layak Higienitas) telah diunggah dengan lengkap.
                    @else
                        Beberapa berkas atau informasi identitas masih belum diisi. Mohon lengkapi seluruh kolom wajib di bawah ini.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end pt-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                Simpan & Update Berkas Tenant
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
