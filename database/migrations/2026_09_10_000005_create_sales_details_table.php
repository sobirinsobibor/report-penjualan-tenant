<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_import_id')->constrained('sales_imports')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('gross_sales', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('sales_import_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_details');
    }
};
