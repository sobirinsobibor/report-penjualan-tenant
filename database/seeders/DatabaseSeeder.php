<?php

namespace Database\Seeders;

use App\Models\Kantin;
use App\Models\Permission;
use App\Models\Role;
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
        // 1. Master Permissions (Abilities)
        $permissionsList = [
            // Kantin
            ['name' => 'kantin.view_any', 'display_name' => 'Lihat Data Kantin', 'group' => 'Kantin'],
            ['name' => 'kantin.create', 'display_name' => 'Tambah Kantin Baru', 'group' => 'Kantin'],
            ['name' => 'kantin.update', 'display_name' => 'Ubah Data Kantin', 'group' => 'Kantin'],
            ['name' => 'kantin.delete', 'display_name' => 'Hapus Kantin', 'group' => 'Kantin'],

            // Tenant
            ['name' => 'tenant.view_any', 'display_name' => 'Lihat Data Tenant', 'group' => 'Tenant'],
            ['name' => 'tenant.create', 'display_name' => 'Tambah Tenant Baru', 'group' => 'Tenant'],
            ['name' => 'tenant.update', 'display_name' => 'Ubah Data Tenant', 'group' => 'Tenant'],
            ['name' => 'tenant.delete', 'display_name' => 'Hapus Tenant', 'group' => 'Tenant'],

            // Pengguna (User)
            ['name' => 'user.view_any', 'display_name' => 'Lihat Daftar Pengguna', 'group' => 'Pengguna'],
            ['name' => 'user.create', 'display_name' => 'Buat Pengguna Baru', 'group' => 'Pengguna'],
            ['name' => 'user.update', 'display_name' => 'Ubah Pengguna', 'group' => 'Pengguna'],
            ['name' => 'user.delete', 'display_name' => 'Hapus Pengguna', 'group' => 'Pengguna'],
            ['name' => 'user.reset_password', 'display_name' => 'Reset Password Pengguna', 'group' => 'Pengguna'],

            // Role & Hak Akses
            ['name' => 'role.view_any', 'display_name' => 'Lihat Role & Hak Akses', 'group' => 'Role (RBAC)'],
            ['name' => 'role.manage', 'display_name' => 'Kelola & Insert Ability ke Role', 'group' => 'Role (RBAC)'],

            // Laporan & Import
            ['name' => 'sales.import', 'display_name' => 'Upload / Import File Excel ESB', 'group' => 'Laporan Penjualan'],
            ['name' => 'sales.view_all', 'display_name' => 'Lihat Laporan Penjualan Global (Semua Tenant)', 'group' => 'Laporan Penjualan'],
            ['name' => 'sales.view_own', 'display_name' => 'Lihat Laporan Penjualan Tenant Milik Sendiri', 'group' => 'Laporan Penjualan'],
            ['name' => 'sales.delete', 'display_name' => 'Hapus Transaksi Penjualan', 'group' => 'Laporan Penjualan'],
        ];

        foreach ($permissionsList as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'group' => $perm['group']]
            );
        }

        // 2. Roles
        $superadminRole = Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Akses mutlak dan tak terbatas ke seluruh modul dan konfigurasi.',
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator Operasional',
                'description' => 'Mengelola kantin, tenant, import file ESB, dan memonitor laporan.',
            ]
        );

        $tenantRole = Role::firstOrCreate(
            ['name' => 'tenant'],
            [
                'display_name' => 'Tenant (Penyewa)',
                'description' => 'Hanya dapat melihat dashboard dan laporan penjualan miliknya sendiri.',
            ]
        );

        // 3. Insert Abilities ke Role tertentu
        // Superadmin: Semua abilities
        $allPermissions = Permission::pluck('id')->toArray();
        $superadminRole->permissions()->sync($allPermissions);

        // Admin: Semua operasional (kecuali kelola role RBAC khusus superadmin)
        $adminPermissions = Permission::where('name', 'not like', 'role.%')->pluck('id')->toArray();
        $adminRole->permissions()->sync($adminPermissions);

        // Tenant: Hanya sales.view_own
        $tenantPermissions = Permission::whereIn('name', ['sales.view_own'])->pluck('id')->toArray();
        $tenantRole->permissions()->sync($tenantPermissions);

        // 4. Sample Kantin & Tenants
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

        Tenant::firstOrCreate([
            'kantin_id' => $kantinBarat->id,
            'name' => 'Bakso Mas Joni',
        ]);

        // 5. Akun Pengguna Tertaut Role
        User::updateOrCreate(
            ['email' => 'superadmin@kantin.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'role_id' => $superadminRole->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@kantin.com'],
            [
                'name' => 'Admin Operasional',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'role_id' => $adminRole->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tenant1@kantin.com'],
            [
                'name' => 'Pengelola Bu Sri',
                'password' => Hash::make('password'),
                'role' => 'tenant',
                'role_id' => $tenantRole->id,
                'tenant_id' => $tenantAyam->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tenant2@kantin.com'],
            [
                'name' => 'Barista Kopi Kenangan',
                'password' => Hash::make('password'),
                'role' => 'tenant',
                'role_id' => $tenantRole->id,
                'tenant_id' => $tenantKopi->id,
            ]
        );

        // 6. Sample Sales Import & Details
        $periodRaw = '01/09/2026 - 07/09/2026';
        $admin = User::where('email', 'admin@kantin.com')->first();

        $import = SalesImport::firstOrCreate(
            [
                'kantin_id' => $kantinUtama->id,
                'period_raw' => $periodRaw,
            ],
            [
                'uploaded_by' => $admin?->id,
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
