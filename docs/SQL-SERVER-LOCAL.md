# SQL Server lokal EPITA

Docker Desktop menyediakan server SQL Server; ekstensi SQL Server VS Code adalah klien. Mac Apple Silicon memakai emulasi linux/amd64 untuk development, bukan konfigurasi production yang didukung Microsoft.

## File
- compose.sqlsrv.yml: server 2022, volume persisten, port 1433 hanya localhost.
- .local/sqlsrv/server.env: password administrator acak, privat.
- .local/sqlsrv/app.json: password akun epita_app, privat.
- .env.sqlsrv.local: konfigurasi Laravel siap pakai, privat.
- .local/sqlsrv/env.before-sqlsrv: salinan konfigurasi sebelum migrasi.
- scripts/php74-sqlsrv: PHP 7.4 dengan driver SQL Server lokal proyek.

Jangan membagikan file rahasia tersebut. Akun epita_app memiliki db_owner khusus development/migration; production wajib memisahkan akun deploy dan akun runtime dengan hak minimum.

## Menjalankan
```sh
./scripts/docker compose -f compose.sqlsrv.yml up -d
./scripts/serve
```
Worker dan scheduler juga menggunakan scripts/php74-sqlsrv. Database tersimpan pada named volume epita_sqlserver_data. Jangan menjalankan compose down -v karena menghapus volume.

## Koneksi VS Code
SQL Server extension → Add Connection:
- Server: 127.0.0.1,1433
- Authentication: SQL Login
- User: epita_app
- Database: epita
- Encrypt: Mandatory/True
- Trust server certificate: True, khusus server development lokal
- Password: ambil DB_PASSWORD dari .env lokal, jangan simpan dalam settings yang dibagikan.

## Pemindahan data
Provision scripts/setup-sqlsrv.php menyiapkan database tanpa mengalihkan .env. Migration SQL Server dilakukan dengan konfigurasi .env.sqlsrv.local, kemudian scripts/copy-sqlite-to-sqlsrv.php menyalin semua tabel kecuali migrations di dalam transaksi. Target harus kosong; constraint diperiksa kembali sebelum commit. SQLite sumber tidak diubah/dihapus. Aplikasi dialihkan hanya setelah data dan koneksi terverifikasi.

## Pemulihan
Hentikan proses PHP, salin .local/sqlsrv/env.before-sqlsrv ke .env, jalankan scripts/php74 artisan config:clear, kemudian jalankan kembali server. Ini kembali ke snapshot SQLite sebelum cutover; perubahan baru di SQL Server tidak otomatis ikut kembali.

## Hasil setup 15 Oktober 2026
SQL Server 2022 berhasil dijalankan. Seluruh 9 migration lulus pada SQL Server. Data sumber berhasil disalin dengan verifikasi jumlah per tabel dan constraint, termasuk 5 akun, 15 equipment, 29 laporan, 85 approval history. .env aktif menggunakan sqlsrv. Login supervisor dan dashboard berhasil diuji melalui browser. Profil VS Code EPITA Lokal disiapkan tanpa password tersimpan.

VS Code: Terminal → Run Task menyediakan EPITA: SQL Server, EPITA: Web, EPITA: Queue, EPITA: Scheduler. Jangan menjalankan task Web kedua bila port 8080 sudah aktif.

Validasi akhir: seluruh 40 test / 162 assertion lulus pada SQL Server epita_test (56 detik), serta SQLite regression (8 detik). Hasil SQL Server di docs/sqlsrv-test-results.txt. Worker database queue dan scheduler development aktif. Backup SQLite disimpan di .local/sqlsrv/database.before-sqlsrv.sqlite.
