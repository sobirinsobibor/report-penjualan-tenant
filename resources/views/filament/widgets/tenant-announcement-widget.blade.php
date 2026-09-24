<x-filament-widgets::widget>
    @php
        $announcements = $this->getAnnouncements();
    @endphp

    @if($announcements->count() > 0)
        <x-filament::section
            icon="heroicon-o-megaphone"
            icon-color="warning"
            heading="Pengumuman Operasional Foodcourt"
            description="Informasi & jadwal resmi dari manajemen foodcourt."
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($announcements as $announcement)
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background-color: #fafafa; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 9999px; 
                                    @if($announcement->category === 'maintenance') background-color: #fef3c7; color: #92400e;
                                    @elseif($announcement->category === 'stock_opname') background-color: #dbeafe; color: #1e40af;
                                    @elseif($announcement->category === 'promo') background-color: #d1fae5; color: #065f46;
                                    @else background-color: #f3f4f6; color: #374151; @endif">
                                    {{ $announcement->category_label }}
                                </span>
                                @if($announcement->event_date)
                                    <span style="font-size: 0.75rem; color: #6b7280; font-weight: 600;">
                                        📅 {{ $announcement->event_date->format('d M Y') }}
                                    </span>
                                @endif
                            </div>

                            <h4 style="font-weight: 700; font-size: 0.95rem; color: #111827; margin: 0 0 0.35rem 0;">
                                {{ $announcement->title }}
                            </h4>

                            <p style="font-size: 0.8rem; color: #4b5563; line-height: 1.4; margin: 0;">
                                {{ $announcement->content }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
