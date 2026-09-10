<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kantin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function salesImports(): HasMany
    {
        return $this->hasMany(SalesImport::class);
    }
}
