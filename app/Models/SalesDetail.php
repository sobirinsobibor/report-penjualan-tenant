<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_import_id',
        'tenant_id',
        'item_name',
        'qty',
        'gross_sales',
        'discount',
        'grand_total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'gross_sales' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function salesImport(): BelongsTo
    {
        return $this->belongsTo(SalesImport::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
