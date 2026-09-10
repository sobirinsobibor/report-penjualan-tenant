<?php

namespace Database\Seeders;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Superadmin & Admin
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@kantin.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@kantin.com'],
            [
                'name' => 'Admin Operasional',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Sample Kantin & Tenants
        $kantinUtama = Kantin::firstOrCreate(['name' => 'Kantin Gedung Utama']);
        $kantinBarat = Kantin::firstOrCreate(['name' => 'Kantin Gedung Barat']);

        $tenantAyam = Tenant::firstOrCreate([
            'kantin_id' => $kantinUtama->id,
            'name' => 'Ayam Geprek Bu Sri',
        ]);

        $tenantKopi = Tenant::firstOrCreate([
            'kantin_id' => $kantinUtama->id,
            'name' => 'Kopi Kenangan Mantan',
        ]);

        $tenantBakso = Tenant::firstOrCreate([
            'kantin_id' => $kantinBarat->id,
            'name' => 'Bakso Mas Joni',
        ]);

        // 3. Sample Tenant User
        User::firstOrCreate(
            ['email' => 'tenant1@kantin.com'],
            [
                'name' => 'Pengelola Bu Sri',
                'password' => Hash::make('password'),
                'role' => 'tenant',
                'tenant_id' => $tenantAyam->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'tenant2@kantin.com'],
            [
                'name' => 'Barista Kopi Kenangan',
                'password' => Hash::make('password'),
                'role' => 'tenant',
                'tenant_id' => $tenantKopi->id,
            ]
        );

        // 4. Sample Sales Import & Details
        $periodRaw = '01/09/2026 - 07/09/2026';
        $import = SalesImport::firstOrCreate(
            [
                'kantin_id' => $kantinUtama->id,
                'period_raw' => $periodRaw,
            ],
            [
                'uploaded_by' => $admin->id,
                'file_name' => 'ESB_Sales_Report_W1_Sep2026.xlsx',
                'period_start' => '2026-09-01',
                'period_end' => '2026-09-07',
                'total_rows' => 4,
                'total_amount' => 935000,
            ]
        );

        if ($import->wasRecentlyCreated) {
            SalesDetail::insert([
                [
                    'sales_import_id' => $import->id,
                    'tenant_id' => $tenantAyam->id,
                    'item_name' => 'Paket Geprek Original Level 3',
                    'qty' => 15,
                    'gross_sales' => 300000,
                    'discount' => 0,
                    'grand_total' => 300000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'sales_import_id' => $import->id,
                    'tenant_id' => $tenantAyam->id,
                    'item_name' => 'Es Teh Manis Jumbo',
                    'qty' => 20,
                    'gross_sales' => 100000,
                    'discount' => 10000,
                    'grand_total' => 90000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'sales_import_id' => $import->id,
                    'tenant_id' => $tenantKopi->id,
                    'item_name' => 'Kopi Kenangan Mantan R',
                    'qty' => 25,
                    'gross_sales' => 450000,
                    'discount' => 25000,
                    'grand_total' => 425000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'sales_import_id' => $import->id,
                    'tenant_id' => $tenantKopi->id,
                    'item_name' => 'Roti Coklat Klasik',
                    'qty' => 10,
                    'gross_sales' => 120000,
                    'discount' => 0,
                    'grand_total' => 120000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
