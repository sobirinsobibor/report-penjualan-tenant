<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use Filament\Widgets\Widget;

class TenantAnnouncementWidget extends Widget
{
    protected string $view = 'filament.widgets.tenant-announcement-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getAnnouncements()
    {
        $user = auth()->user();
        $kantinId = $user?->tenant?->kantin_id;

        return Announcement::where('is_active', true)
            ->when($kantinId, function ($q) use ($kantinId) {
                $q->where(function ($sub) use ($kantinId) {
                    $sub->whereNull('kantin_id')->orWhere('kantin_id', $kantinId);
                });
            })
            ->latest()
            ->take(5)
            ->get();
    }
}
