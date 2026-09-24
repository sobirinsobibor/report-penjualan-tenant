<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'period_raw',
        'period_start',
        'period_end',
        'gross_sales',
        'revenue_share_percentage',
        'revenue_share_amount',
        'operational_fee',
        'other_deductions',
        'deduction_notes',
        'settlement_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_sales' => 'decimal:2',
        'revenue_share_percentage' => 'decimal:2',
        'revenue_share_amount' => 'decimal:2',
        'operational_fee' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function calculateForTenant(Tenant $tenant, string $startDate, string $endDate, float $otherDeductions = 0, ?string $deductionNotes = null): self
    {
        $detailsQuery = SalesDetail::where('tenant_id', $tenant->id)
            ->whereHas('salesImport', function ($query) use ($startDate, $endDate) {
                $query->whereDate('period_start', '>=', $startDate)
                    ->whereDate('period_end', '<=', $endDate);
            });

        $grossSales = (float) $detailsQuery->sum('grand_total');
        $feePct = (float) ($tenant->fee_percentage ?? 0);
        $revenueShareAmount = $grossSales * ($feePct / 100);
        $operationalFee = (float) ($tenant->fixed_fee ?? 0);
        $settlementAmount = $grossSales - $revenueShareAmount - $operationalFee - $otherDeductions;

        $periodRaw = date('d M Y', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate));

        return new self([
            'tenant_id' => $tenant->id,
            'period_raw' => $periodRaw,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'gross_sales' => $grossSales,
            'revenue_share_percentage' => $feePct,
            'revenue_share_amount' => $revenueShareAmount,
            'operational_fee' => $operationalFee,
            'other_deductions' => $otherDeductions,
            'deduction_notes' => $deductionNotes,
            'settlement_amount' => $settlementAmount,
            'status' => 'calculated',
        ]);
    }
}
