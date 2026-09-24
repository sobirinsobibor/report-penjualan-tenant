<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('period_raw'); // e.g. "Periode 1–15 September 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_sales', 15, 2)->default(0);
            $table->decimal('revenue_share_percentage', 5, 2)->default(0);
            $table->decimal('revenue_share_amount', 15, 2)->default(0);
            $table->decimal('operational_fee', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->string('deduction_notes')->nullable();
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->string('status')->default('calculated'); // draft, calculated, approved, paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
