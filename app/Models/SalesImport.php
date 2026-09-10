<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'kantin_id',
        'uploaded_by',
        'file_name',
        'period_raw',
        'period_start',
        'period_end',
        'total_rows',
        'total_amount',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_rows' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function kantin(): BelongsTo
    {
        return $this->belongsTo(Kantin::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function salesDetails(): HasMany
    {
        return $this->hasMany(SalesDetail::class);
    }
}
