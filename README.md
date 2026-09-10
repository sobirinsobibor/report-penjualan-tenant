# Analisis Sistem: Web Reporting Penjualan Tenant

## 1. Deskripsi Umum
Sistem **Web Reporting Penjualan Tenant** adalah sebuah platform berbasis web yang berfungsi untuk mengolah dan menyajikan laporan penjualan tenant yang beroperasi di berbagai kantin. Data penjualan tidak diinput secara manual, melainkan diimpor dari hasil *export* sistem ESB (Sales Menu Recapitulation Report). Sistem ini mengedepankan keamanan akses melalui Role-Based Access Control (RBAC).

---

## 2. Hak Akses (Role-Based Access Control / RBAC)
Sistem memiliki 3 role utama dengan batasan akses yang ketat:
*   **Superadmin / Admin:** Memiliki akses penuh terhadap manajemen data master, proses import Excel, manajemen akun, dan dapat melihat laporan penjualan seluruh tenant di semua kantin.
*   **Tenant:** Hanya dapat mengakses halaman dashboard dan laporan penjualan. Data yang ditampilkan dikunci secara otomatis (`tenant_id`) sehingga tenant hanya bisa melihat data penjualan miliknya sendiri.

---

## 3. Modul & Fitur Utama

### A. Modul Autentikasi & Akun
*   **Login & Logout:** Autentikasi menggunakan kombinasi email/username dan password.
*   **Manajemen Akun:** Tidak ada fitur pendaftaran mandiri (*self-registration*). Pembuatan akun sepenuhnya dilakukan oleh Admin.
*   **Reset Password:** Tidak ada fitur lupa password via email. Reset password dikembalikan ke *default* dan hanya bisa dilakukan oleh Admin.

### B. Modul Manajemen Data Master
*   **CRUD Akun User:** Admin dapat membuat akun, mengatur role (Superadmin, Admin, Tenant), mengatur password default, dan menautkan akun tenant ke data tenant terkait.
*   **CRUD Kantin:** Manajemen data Kantin (Branch). Data ini juga dapat terbuat secara otomatis saat proses import.
*   **CRUD Tenant:** Manajemen penyewa (berdasarkan Menu Category) yang terhubung dengan Kantin. Data ini juga dapat terbuat otomatis saat import.

### C. Modul Import Data Penjualan (Core Process)
*   **Upload Excel ESB:** Modul untuk mengunggah file *Sales Menu Recapitulation Report*.
*   **Parsing Metadata:** Membaca informasi *Branch* (Kantin) dan *Period* langsung dari *header* / metadata file Excel.
*   **Validasi Duplikasi:** Mencegah sistem menyimpan data ganda jika kombinasi Kantin dan Periode yang sama diunggah ulang.
*   **Auto-Match / Auto-Create:** 
    *   Mencocokkan *Branch* di Excel ke Kantin di database (jika belum ada, otomatis dibuat).
    *   Mencocokkan *Menu Category* di Excel ke Tenant di database (jika belum ada, otomatis dibuat).
*   **Simpan Transaksi:** Menyimpan seluruh data per baris Excel ke dalam tabel Detail Penjualan.

### D. Modul Laporan Penjualan
*   **Dashboard Total Penjualan Tenant:** Menampilkan akumulasi total pendapatan (*SUM Grand Total*) untuk masing-masing tenant.
*   **Filter Periode:** Opsi untuk menyaring laporan berdasarkan rentang tanggal tertentu.
*   **Laporan Global (Khusus Admin):** Akses untuk melihat seluruh transaksi dari semua kantin dan tenant tanpa batasan filter identitas.

---

## 4. Alur Kerja (Workflow) Import Data
1. **Admin** mengunggah file Excel hasil *export* ESB.
2. **Sistem** mengekstrak metadata dari file untuk mendeteksi `Branch` (Kantin) dan `Period`.
3. **Sistem** melakukan pengecekan di *database*: *Apakah file untuk Kantin dan Periode ini sudah pernah diunggah?*
    *   Jika **Ya**: Proses dibatalkan (Validasi duplikasi).
    *   Jika **Tidak**: Lanjut ke tahap berikutnya.
4. **Sistem** mencocokkan master data:
    *   Jika nama *Branch* tidak ada di sistem, otomatis buat data Kantin baru.
    *   Jika nama *Menu Category* tidak ada di sistem, otomatis buat data Tenant baru.
5. **Sistem** memproses isi Excel baris demi baris dan menyimpannya ke tabel *Transaksi Detail Penjualan*.
6. Data siap disajikan di Dashboard Laporan sesuai dengan hak akses (Role) yang sedang login.