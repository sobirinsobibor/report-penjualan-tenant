# Walkthrough: Web Reporting Penjualan Tenant

Sistem telah selesai diimplementasikan sesuai seluruh spesifikasi pada [README.md](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/README.md).

---

## Akun Bawaan (Default Credentials)

Semua akun menggunakan password bawaan: `password`

| Role | Email | Hak Akses |
|---|---|---|
| **Superadmin** | `superadmin@kantin.com` | Akses penuh master data, import file ESB, semua laporan |
| **Admin** | `admin@kantin.com` | Akses penuh master data, import file ESB, semua laporan |
| **Tenant 1** | `tenant1@kantin.com` | Khusus melihat data penjualan *Ayam Geprek Bu Sri* |
| **Tenant 2** | `tenant2@kantin.com` | Khusus melihat data penjualan *Kopi Kenangan Mantan* |

---

## Modul & Fitur yang Diimplementasikan

### 1. Keamanan & Role-Based Access Control (RBAC)
- **Superadmin & Admin**:
  - Mengelola data Kantin di [KantinResource.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Resources/Kantins/KantinResource.php).
  - Mengelola data Tenant di [TenantResource.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Resources/Tenants/TenantResource.php).
  - Mengelola Akun Pengguna di [UserResource.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Resources/Users/UserResource.php), menautkan akun tenant ke data tenant, serta tombol **Reset Password** ke default.
  - Akses halaman import file Excel ESB di [ImportSales.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Pages/ImportSales.php).
  - Akses laporan global seluruh kantin & tenant di [SalesDetailResource.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Resources/SalesDetails/SalesDetailResource.php).
  - Widget ringkasan omzet global di [AdminSalesOverviewWidget.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Widgets/AdminSalesOverviewWidget.php).
- **Tenant**:
  - Akses ke menu master data & import diblokir (`403 Forbidden`).
  - Laporan penjualan di-scope otomatis hanya menampilkan data milik `tenant_id` akun terkait.
  - Dashboard khusus tenant menampilkan total pendapatan, kuantitas terjual, dan menu terlaris miliknya di [TenantSalesStatsWidget.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Widgets/TenantSalesStatsWidget.php).

### 2. Core Service Import Excel ESB ([EsbImportService.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Services/EsbImportService.php))
- **Mendukung File**: `.xlsx` dan `.csv` (menggunakan OpenSpout).
- **Parsing Metadata Header**: Otomatis mendeteksi nama Branch/Kantin dan Periode pada baris header file.
- **Validasi Anti-Duplikasi**: Menolak file jika kombinasi Kantin & Periode yang sama sudah pernah diimport ke database.
- **Auto-Match / Auto-Create**: Otomatis membuat entri Kantin & Tenant baru di database jika belum pernah ada sebelumnya.
- **Penyimpanan Detail**: Menyimpan baris produk, kuantitas, harga kotor, diskon, dan grand total ke tabel detail dalam transaksi database yang aman.

---

## Verifikasi & Hasil Pengujian

Semua pengujian otomatis berhasil dijalankan dengan 100% kelulusan:
- `Tests\Unit\ExampleTest`: PASS
- `Tests\Feature\EsbImportTest`: PASS (Verifikasi import, metadata parsing, auto-creation, dan pencegahan duplikasi)
- `Tests\Feature\SystemRbacAndReportTest`: PASS (Verifikasi isolasi data tenant, pembatasan hak akses Admin vs Tenant)
- `Tests\Feature\ExampleTest`: PASS (Verifikasi alur redirect ke dashboard login)

# Walkthrough: Web Reporting Penjualan Tenant & Dynamic RBAC

Sistem telah dilengkapi dengan arsitektur **Role-Based Access Control (RBAC)** dinamis dengan kemampuan memasukkan (*insert*) **Abilities** ke dalam Role tertentu, baik melalui UI Filament maupun secara terprogram (seeders/code).

---

## 1. Arsitektur Database RBAC

Sistem memiliki 3 tabel inti untuk RBAC:
1. **`roles`**: Menyimpan daftar role (`name`, `display_name`, `description`).
2. **`permissions`**: Menyimpan daftar kemampuan/hak akses (*abilities*) spesifik (`name`, `display_name`, `group`).
3. **`permission_role`**: Tabel pivot penghubung many-to-many antara Role dan Permission.
4. **`users.role_id`**: Foreign key relasi langsung ke tabel `roles`.

---

## 2. Daftar Abilities (Hak Akses)

| Modul | Ability Code | Nama Tampilan |
|---|---|---|
| **Kantin** | `kantin.view_any` | Lihat Data Kantin |
| | `kantin.create` | Tambah Kantin Baru |
| | `kantin.update` | Ubah Data Kantin |
| | `kantin.delete` | Hapus Kantin |
| **Tenant** | `tenant.view_any` | Lihat Data Tenant |
| | `tenant.create` | Tambah Tenant Baru |
| | `tenant.update` | Ubah Data Tenant |
| | `tenant.delete` | Hapus Tenant |
| **Pengguna** | `user.view_any` | Lihat Daftar Pengguna |
| | `user.create` | Buat Pengguna Baru |
| | `user.update` | Ubah Pengguna |
| | `user.delete` | Hapus Pengguna |
| | `user.reset_password` | Reset Password Pengguna ke Default |
| **Role (RBAC)**| `role.view_any` | Lihat Role & Hak Akses |
| | `role.manage` | Kelola & Insert Ability ke Role |
| **Laporan** | `sales.import` | Upload / Import File Excel ESB |
| | `sales.view_all` | Lihat Laporan Penjualan Global (Semua Tenant) |
| | `sales.view_own` | Lihat Laporan Penjualan Tenant Sendiri |
| | `sales.delete` | Hapus Transaksi Penjualan |

---

## 3. Pemetaan Default Role ke Abilities (*Insert Ability ke Role*)

1. **Superadmin (`superadmin`)**:
   - Memiliki **SEMUA abilities** secara otomatis (Bypass & All permissions synced).
   - Memiliki akses eksklusif ke menu **Kelola Role (RBAC)** di `/dashboard/roles`.
2. **Admin Operasional (`admin`)**:
   - Diberikan abilities operasional: kelola Kantin, Tenant, Pengguna, Import Excel ESB, dan Laporan Global.
   - Tidak memiliki akses ke menu Role RBAC secara default (dapat diaktifkan jika ability `role.view_any` di-assign).
3. **Tenant (`tenant`)**:
   - Diberikan ability: `sales.view_own`.
   - Data laporan penjualan dikunci otomatis sesuai `tenant_id` akun terkait.

---

## 4. Antarmuka Manajemen Role di Filament ([RoleResource.php](file:///c:/Users/Bintang/Documents/GitHub/report-penjualan-tenant/app/Filament/Resources/Roles/RoleResource.php))

- **Lokasi Menu**: `Data Master` &rarr; `Kelola Role (RBAC)` (`/dashboard/roles`).
- **Fitur Form**:
  - Input Kode Role dan Nama Tampilan.
  - Multi-checkbox **"Insert Abilities / Hak Akses ke Role"** untuk mencentang/mencabut kemampuan akses yang dimiliki suatu role secara instan.
- **Tampilan Tabel**:
  - Kolom Nama Role, Badge Kode, Counter Total Abilities yang aktif, dan Total Pengguna yang memakai role tersebut.

---

## 5. Cara Menggunakan via Kode / Seeder

Menyematkan (*insert*) ability ke dalam role dapat dilakukan langsung lewat Eloquent helper:

```php
use App\Models\Role;

// Menambahkan ability baru ke role 'admin'
$role = Role::where('name', 'admin')->first();
$role->giveAbilities(['role.view_any', 'user.create']);

// Mengecek ability pada user
$user->hasAbility('kantin.create'); // return true/false
```

---

## 6. Hasil Verifikasi Pengujian

Semua 9 test otomatis pada `php artisan test` **LULUS (47 assertions)**:
- `test_superadmin_has_all_abilities_and_can_access_roles`: PASS
- `test_admin_has_operational_abilities_but_cannot_access_roles`: PASS
- `test_can_insert_ability_to_role_and_user_inherits_it`: PASS
- `test_tenant_cannot_access_master_data_or_import`: PASS
- `test_tenant_query_is_scoped_to_their_tenant_id`: PASS
- `test_can_import_sales_report_and_auto_create_kantin_and_tenants`: PASS
- `test_prevents_duplicate_import_for_same_kantin_and_period`: PASS
- `test_the_application_returns_a_successful_response`: PASS
- `test_that_true_is_true`: PASS
