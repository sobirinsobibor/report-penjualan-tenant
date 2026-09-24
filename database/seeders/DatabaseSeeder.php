<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Kantin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Settlement;
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

        // 3. Sync Permissions
        $allPermissions = Permission::pluck('id')->toArray();
        $superadminRole->permissions()->sync($allPermissions);

        $adminPermissions = Permission::where('name', 'not like', 'role.%')->pluck('id')->toArray();
        $adminRole->permissions()->sync($adminPermissions);

        $tenantPermissions = Permission::whereIn('name', ['sales.view_own'])->pluck('id')->toArray();
        $tenantRole->permissions()->sync($tenantPermissions);

        // 4. Sample Kantin
        $kantin = Kantin::firstOrCreate(['name' => 'AFP Kampus B']);

        // 5. Tenant List
        $tenantsData = [
            '01 HENI SRI WAHYUNI' => ['fee' => 15.00, 'target' => 55000000, 'email' => 'tenant1@kantin.com'],
            '02 SITI ISDAIYAH'    => ['fee' => 15.00, 'target' => 55000000, 'email' => 'tenant2@kantin.com'],
            '03 SITI ISNAINI'     => ['fee' => 15.00, 'target' => 50000000, 'email' => 'tenant3@kantin.com'],
            '04 WELAS'            => ['fee' => 15.00, 'target' => 48000000, 'email' => 'tenant4@kantin.com'],
            '05 SRI ASTUTIK'      => ['fee' => 15.00, 'target' => 52000000, 'email' => 'tenant5@kantin.com'],
            '08 LILIS SUSINDAWATI'=> ['fee' => 15.00, 'target' => 60000000, 'email' => 'tenant8@kantin.com'],
            '10 SLAMET'           => ['fee' => 15.00, 'target' => 55000000, 'email' => 'tenant10@kantin.com'],
            '11 IMAM ROBAI'       => ['fee' => 15.00, 'target' => 70000000, 'email' => 'tenant11@kantin.com'],
            '13 DWI HERU'         => ['fee' => 15.00, 'target' => 50000000, 'email' => 'tenant13@kantin.com'],
            '15 SEGO NDOG'        => ['fee' => 15.00, 'target' => 45000000, 'email' => 'tenant15@kantin.com'],
            '16 LISTYOWATI'       => ['fee' => 15.00, 'target' => 58000000, 'email' => 'tenant16@kantin.com'],
            '17 SRI SULISTYAWATI' => ['fee' => 15.00, 'target' => 55000000, 'email' => 'tenant17@kantin.com'],
        ];

        $tenantModels = [];
        foreach ($tenantsData as $tenantName => $info) {
            $t = Tenant::firstOrCreate(
                ['kantin_id' => $kantin->id, 'name' => $tenantName],
                [
                    'fee_percentage' => $info['fee'],
                    'target_omzet' => $info['target'],
                    'fixed_fee' => 50000,
                ]
            );
            $tenantModels[$tenantName] = $t;
        }

        // 6. User Accounts
        User::updateOrCreate(
            ['email' => 'superadmin@kantin.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'role_id' => $superadminRole->id,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@kantin.com'],
            [
                'name' => 'Admin Operasional',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'role_id' => $adminRole->id,
            ]
        );

        foreach ($tenantsData as $tenantName => $info) {
            User::updateOrCreate(
                ['email' => $info['email']],
                [
                    'name' => "Tenant " . $tenantName,
                    'password' => Hash::make('password'),
                    'role' => 'tenant',
                    'role_id' => $tenantRole->id,
                    'tenant_id' => $tenantModels[$tenantName]->id,
                ]
            );
        }

        // 7. Operational Announcements
        Announcement::firstOrCreate(
            ['title' => 'Maintenance Listrik Outlet Foodcourt'],
            [
                'kantin_id' => $kantin->id,
                'category' => 'maintenance',
                'event_date' => '2026-09-23',
                'content' => 'Pemberitahuan pemeliharaan instalasi listrik utama gedung foodcourt pada tanggal 23 September 2026 pukul 06.00 - 08.00 WIB.',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        Announcement::firstOrCreate(
            ['title' => 'Jadwal Stock Opname Bulanan'],
            [
                'kantin_id' => $kantin->id,
                'category' => 'stock_opname',
                'event_date' => '2026-09-25',
                'content' => 'Pelaksanaan stock opname dan audit sanitasi outlet akan dilaksanakan pada Jumat, 25 September 2026 setelah jam operasional.',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        Announcement::firstOrCreate(
            ['title' => 'Program Promo Foodcourt Fair 2026'],
            [
                'kantin_id' => $kantin->id,
                'category' => 'promo',
                'event_date' => '2026-09-28',
                'content' => 'Ikuti program promo cashback 20% khusus pembayaran QRIS selama pekan Airlangga Foodcourt Fair.',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        // 8. Sample ESB Sales Import
        $periodRaw = '01-09-2026 - 04-09-2026';

        $import = SalesImport::firstOrCreate(
            [
                'kantin_id' => $kantin->id,
                'period_raw' => $periodRaw,
            ],
            [
                'uploaded_by' => $admin->id,
                'file_name' => 'Sales Menu Recapitulation Report - 20260904182016.xlsx',
                'period_start' => '2026-09-01',
                'period_end' => '2026-09-04',
                'total_rows' => 28,
                'total_amount' => 450500,
            ]
        );

        if ($import->wasRecentlyCreated) {
            $salesItems = [
                // 01 HENI SRI WAHYUNI
                ['tenant' => '01 HENI SRI WAHYUNI', 'item' => '01 Gado-Gado', 'qty' => 2, 'gross' => 28000, 'discount' => 0, 'net' => 28000],

                // 02 SITI ISDAIYAH
                ['tenant' => '02 SITI ISDAIYAH', 'item' => '02 Keripik Usus', 'qty' => 4, 'gross' => 24000, 'discount' => 0, 'net' => 24000],
                ['tenant' => '02 SITI ISDAIYAH', 'item' => '02 Nasi Goreng Ayam', 'qty' => 1, 'gross' => 16000, 'discount' => 0, 'net' => 16000],
                ['tenant' => '02 SITI ISDAIYAH', 'item' => '02 Seblak Kering', 'qty' => 4, 'gross' => 24000, 'discount' => 0, 'net' => 24000],
                ['tenant' => '02 SITI ISDAIYAH', 'item' => '02 Telur', 'qty' => 1, 'gross' => 5000, 'discount' => 0, 'net' => 5000],

                // 03 SITI ISNAINI
                ['tenant' => '03 SITI ISNAINI', 'item' => '03 Bakwan', 'qty' => 1, 'gross' => 14000, 'discount' => 0, 'net' => 14000],

                // 04 WELAS
                ['tenant' => '04 WELAS', 'item' => '04 Nasi Campur Ati Ampela', 'qty' => 1, 'gross' => 16000, 'discount' => 0, 'net' => 16000],
                ['tenant' => '04 WELAS', 'item' => '04 Nasi Campur Krengsengan', 'qty' => 1, 'gross' => 17000, 'discount' => 0, 'net' => 17000],
                ['tenant' => '04 WELAS', 'item' => '04 Take Away', 'qty' => 1, 'gross' => 1000, 'discount' => 0, 'net' => 1000],

                // 05 SRI ASTUTIK
                ['tenant' => '05 SRI ASTUTIK', 'item' => '05 Nasi Ayam Cili Padi', 'qty' => 1, 'gross' => 16500, 'discount' => 0, 'net' => 16500],

                // 08 LILIS SUSINDAWATI
                ['tenant' => '08 LILIS SUSINDAWATI', 'item' => '08 Sate Asin (Lontong/Nasi Putih/Nasi Daun Jeruk)', 'qty' => 1, 'gross' => 18000, 'discount' => 0, 'net' => 18000],

                // 10 SLAMET
                ['tenant' => '10 SLAMET', 'item' => '10 Pangsit Mi Ayam Yamin', 'qty' => 1, 'gross' => 16000, 'discount' => 0, 'net' => 16000],
                ['tenant' => '10 SLAMET', 'item' => '10 Take Away', 'qty' => 1, 'gross' => 2000, 'discount' => 0, 'net' => 2000],

                // 11 IMAM ROBAI
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Indomi Spesial', 'qty' => 3, 'gross' => 45000, 'discount' => 0, 'net' => 45000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Indomie Bangladesh', 'qty' => 1, 'gross' => 15000, 'discount' => 0, 'net' => 15000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Indomie Jomblo', 'qty' => 10, 'gross' => 60000, 'discount' => 0, 'net' => 60000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Internet (Indomie, Telur, Kornet)', 'qty' => 2, 'gross' => 30000, 'discount' => 0, 'net' => 30000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Mie Ayam Geprek', 'qty' => 1, 'gross' => 15000, 'discount' => 0, 'net' => 15000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Take Away 2K', 'qty' => 4, 'gross' => 8000, 'discount' => 0, 'net' => 8000],
                ['tenant' => '11 IMAM ROBAI', 'item' => '11 Toping 5K', 'qty' => 4, 'gross' => 20000, 'discount' => 0, 'net' => 20000],

                // 13 DWI HERU
                ['tenant' => '13 DWI HERU', 'item' => '13 Geprek Crispy/Bakar Ayam', 'qty' => 1, 'gross' => 16500, 'discount' => 0, 'net' => 16500],
                ['tenant' => '13 DWI HERU', 'item' => '13 Mozarella', 'qty' => 1, 'gross' => 5000, 'discount' => 0, 'net' => 5000],

                // 15 SEGO NDOG
                ['tenant' => '15 SEGO NDOG', 'item' => '15 Sego Ndog Jumbo/Isian', 'qty' => 1, 'gross' => 18000, 'discount' => 0, 'net' => 18000],

                // 16 LISTYOWATI
                ['tenant' => '16 LISTYOWATI', 'item' => '16 Nasi Rawon', 'qty' => 1, 'gross' => 21000, 'discount' => 0, 'net' => 21000],
                ['tenant' => '16 LISTYOWATI', 'item' => '16 Nasi Sop Ayam Ungkep', 'qty' => 2, 'gross' => 28000, 'discount' => 0, 'net' => 28000],
                ['tenant' => '16 LISTYOWATI', 'item' => '16 Rica Rica Ayam', 'qty' => 1, 'gross' => 16000, 'discount' => 0, 'net' => 16000],
                ['tenant' => '16 LISTYOWATI', 'item' => '16 Take Away', 'qty' => 2, 'gross' => 5000, 'discount' => 0, 'net' => 5000],

                // 17 SRI SULISTYAWATI
                ['tenant' => '17 SRI SULISTYAWATI', 'item' => '17 Asem Asem Iga', 'qty' => 1, 'gross' => 25000, 'discount' => 0, 'net' => 25000],
                ['tenant' => '17 SRI SULISTYAWATI', 'item' => '17 Nasi Opor Ayam', 'qty' => 1, 'gross' => 16000, 'discount' => 0, 'net' => 16000],
            ];

            $now = now();
            $insertBatch = [];

            foreach ($salesItems as $row) {
                $tenantModel = $tenantModels[$row['tenant']] ?? null;
                if ($tenantModel) {
                    $insertBatch[] = [
                        'sales_import_id' => $import->id,
                        'tenant_id' => $tenantModel->id,
                        'item_name' => $row['item'],
                        'qty' => $row['qty'],
                        'gross_sales' => $row['gross'],
                        'discount' => $row['discount'],
                        'grand_total' => $row['net'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            SalesDetail::insert($insertBatch);
        }

        // 9. Sample Settlement Records
        foreach ($tenantModels as $name => $tenantModel) {
            Settlement::firstOrCreate(
                [
                    'tenant_id' => $tenantModel->id,
                    'period_raw' => 'Periode 1–15 September 2026',
                ],
                [
                    'period_start' => '2026-09-01',
                    'period_end' => '2026-09-15',
                    'gross_sales' => 25000000,
                    'revenue_share_percentage' => 15.00,
                    'revenue_share_amount' => 3750000,
                    'operational_fee' => 50000,
                    'other_deductions' => 100000,
                    'deduction_notes' => 'Biaya Maintenance Listrik & Kebersihan',
                    'settlement_amount' => 21100000,
                    'status' => 'approved',
                    'notes' => 'Laporan settlement transparan periode 1–15 September 2026.',
                    'created_by' => $admin->id,
                ]
            );
        }
    }
}
