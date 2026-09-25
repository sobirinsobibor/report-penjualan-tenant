<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'kantin_id',
        'name',
        'fee_percentage',
        'target_omzet',
        'fixed_fee',
        'nomor_halal',
        'owner_name',
        'nik',
        'no_kk',
        'phone',
        'address',
        'birth_place_date',
        'file_ktp',
        'file_kk',
        'file_npwp',
        'file_sertifikat_higenitas',
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'target_omzet' => 'decimal:2',
        'fixed_fee' => 'decimal:2',
    ];

    public function isDocumentComplete(): bool
    {
        return !empty($this->owner_name)
            && !empty($this->nik)
            && !empty($this->no_kk)
            && !empty($this->phone)
            && !empty($this->address)
            && !empty($this->file_ktp)
            && !empty($this->file_kk)
            && !empty($this->file_npwp)
            && !empty($this->file_sertifikat_higenitas);
    }

    public function getDocumentStatusAttribute(): string
    {
        return $this->isDocumentComplete() ? 'Lengkap' : 'Belum Lengkap';
    }

    public function kantin(): BelongsTo
    {
        return $this->belongsTo(Kantin::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function salesDetails(): HasMany
    {
        return $this->hasMany(SalesDetail::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }
}
