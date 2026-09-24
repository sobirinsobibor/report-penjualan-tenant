<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'kantin_id',
        'title',
        'category',
        'event_date',
        'content',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function kantin(): BelongsTo
    {
        return $this->belongsTo(Kantin::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'maintenance' => 'Maintenance Listrik / Fasilitas',
            'stock_opname' => 'Jadwal Stock Opname',
            'promo' => 'Program Promo Foodcourt',
            default => 'Informasi Umum',
        };
    }

    public function getCategoryBadgeColorAttribute(): string
    {
        return match ($this->category) {
            'maintenance' => 'warning',
            'stock_opname' => 'info',
            'promo' => 'success',
            default => 'gray',
        };
    }
}
