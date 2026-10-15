# Hasil verifikasi

Tanggal: 14 Oktober 2026 (Asia/Jakarta). Runtime: **PHP 7.4.33**, Laravel 8.83.29, PHPUnit 9.6.38. Database test: SQLite in-memory.

```text
OK (39 tests, 156 assertions)
```

Laporan lengkap: [phpunit-results.txt](phpunit-results.txt).

Cakupan: lima role login dan redirect; akun nonaktif; API login/token; plant scope; master CRUD permission; meter presisi besar dan dua desimal; lintas tengah malam; pembagi rasio nol; overlap assignment/report; override beralasan; submit → return → perbaikan → verify → approve; larangan approve sendiri/tanpa permission; double approval; snapshot dan revisi approved; histori setelah soft delete; breakdown → maintenance → release; foto wajib, file berbahaya ditolak, thumbnail privat dan download scope; staging SAP valid/error/duplicate/tanggal/mapping; MATCHED/VALUE_MISMATCH/DATE_MISMATCH/ONLY_LOCAL/ONLY_SAP/MAPPING_MISSING; raw staging dan rollback; filter semua jenis laporan; dashboard dari fixtures; ekspor XLSX/CSV/PDF; keamanan formula dan kode leading zero; role assignment; overdue tidak duplikat; antrean dan download privat.

Verifikasi tambahan:
- Semua migration dijalankan pada database demo lokal.
- View cache dan route cache berhasil; Composer manifest valid.
- Build lokal Bootstrap 5/Chart.js berhasil. Dependency frontend audit: 0 vulnerabilities.
- Database queue worker nyata memproses satu ekspor sampai COMPLETED; file privat tersimpan.
- Browser: login supervisor → dashboard, grafik dari database, form mobile 390px tanpa overflow, filter pilihan plant. Screenshot di docs/screenshots.
- JavaScript dashboard/common diperiksa dengan node --check.

Batas: suite tidak menggunakan MySQL/MariaDB. Uji row-locking bersamaan, SMTP nyata, beban besar, dan restore backup pada staging masih diperlukan. Audit Composer mencatat lima advisory pada stack legacy; lihat SECURITY.md.

## React Inertia / mobile — 15 Oktober 2026
- PHP 7.4.33 + SQL Server epita_test: **51 tests, 215 assertions**, lulus. Log: react-sqlsrv-tests.txt.
- SQLite regression terpisah: **51 tests, 215 assertions**, lulus. Log: react-sqlite-tests.txt. SQLite bukan database pusat aplikasi aktif.
- React Testing Library/Vitest: **3 tests**, lulus (nested form/label, empty state, escaping/status).
- Mobile orchestration: **7 tests**, lulus (retry, status final server-wins, konflik, save/foto/submit, koneksi putus dan replay key).
- Bundle Hermes Android/iOS: export berhasil, log mobile-build-results.txt. Bukan APK/IPA; belum diuji pada perangkat/emulator.
- Browser React: 50 kombinasi halaman/lebar 360/768/1024/1366/1920 tanpa horizontal overflow; 9 halaman admin/SAP berhasil dirender tanpa error JavaScript. Log responsif react-responsive-results.json.
- Screenshot React final: screenshots/react-dashboard-desktop.png dan react-dashboard-mobile.png.
- Lighthouse, SecureStore/SQLite/kamera pada perangkat, dan mode pesawat nyata belum diuji. Tidak ada klaim penerimaan production untuk bagian tersebut.
- Security audit legacy belum bersih. Rincian batas rilis di REACT-MOBILE-DELIVERY.md.
