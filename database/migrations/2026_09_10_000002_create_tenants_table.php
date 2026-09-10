<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantin_id')->constrained('kantins')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['kantin_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
