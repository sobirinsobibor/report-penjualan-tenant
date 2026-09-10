<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantin_id')->constrained('kantins')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('period_raw');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['kantin_id', 'period_raw']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_imports');
    }
};
