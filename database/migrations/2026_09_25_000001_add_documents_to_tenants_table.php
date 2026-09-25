<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('nomor_halal')->nullable()->after('fixed_fee');
            $table->string('owner_name')->nullable()->after('nomor_halal');
            $table->string('nik')->nullable()->after('owner_name');
            $table->string('no_kk')->nullable()->after('nik');
            $table->string('phone')->nullable()->after('no_kk');
            $table->text('address')->nullable()->after('phone');
            $table->string('birth_place_date')->nullable()->after('address');
            $table->string('file_ktp')->nullable()->after('birth_place_date');
            $table->string('file_kk')->nullable()->after('file_ktp');
            $table->string('file_npwp')->nullable()->after('file_kk');
            $table->string('file_sertifikat_higenitas')->nullable()->after('file_npwp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
