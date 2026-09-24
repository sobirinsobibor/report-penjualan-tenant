<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('target_omzet', 15, 2)->default(55000000.00)->after('fee_percentage');
            $table->decimal('fixed_fee', 15, 2)->default(0.00)->after('target_omzet');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['target_omzet', 'fixed_fee']);
        });
    }
};
