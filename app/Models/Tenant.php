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
    ];

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
}
