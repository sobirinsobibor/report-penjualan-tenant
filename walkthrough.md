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
