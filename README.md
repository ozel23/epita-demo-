# Sistem Operasional Alat & VRA

Aplikasi Laravel 8 berbahasa Indonesia untuk aktivitas alat, rencana kerja, penugasan, BBM, approval, breakdown/maintenance terpisah, dan rekonsiliasi SAP IW47. Target runtime **PHP 7.4.33**, database produksi MySQL 8/MariaDB, zona waktu Asia/Jakarta.

## Menjalankan workspace ini

PHP 7.4 terpasang berdampingan dengan PHP bawaan mesin. Gunakan wrapper proyek tanpa mengubah PHP global:

```sh
./scripts/serve
./scripts/php74-sqlsrv artisan queue:work --queue=default --timeout=300 --tries=1
```

Database SQLite lokal sudah dimigrasikan dan diisi data demo. Buka http://127.0.0.1:8080. Kredensial di bawah hanya untuk lokal/dev:

| Username | Email | Peran |
|---|---|---|
| operator | operator@vra.test | Operator |
| supervisor | supervisor@vra.test | Supervisor / Mandor |
| admin | admin@vra.test | Admin / Kerani |
| maintenance | maintenance@vra.test | Maintenance |
| manager | manager@vra.test | Manager / Asisten |

Password demo semuanya: `DemoVra!2026`. Seeder akun demo tidak boleh dijalankan pada produksi.

## Instalasi bersih

```sh
composer install
cp .env.example .env
php artisan key:generate
# Konfigurasikan MySQL di .env; gunakan PHP 7.4 untuk perintah PHP.
php artisan migrate
php artisan db:seed
# Opsional, hanya lokal/testing:
php artisan db:seed --class=DemoScenarioSeeder
npm ci
npm run build
php artisan serve
```

PHP extensions: bcmath, ctype, fileinfo, gd, json, mbstring, openssl, PDO MySQL/SQLite, tokenizer, xml, zip. Composer mengunci resolusi paket ke PHP 7.4.33. Assets Bootstrap 5 dan Chart.js tersedia di `public/assets`; build frontend tidak wajib untuk memulai demo. File evidence/import/export privat disimpan pada `storage/app`, bukan public disk.

## Pengujian

```sh
./scripts/php74 vendor/bin/phpunit --testdox
./scripts/php74 artisan view:cache
./scripts/php74 artisan route:list
```

Test memakai SQLite in-memory dan tidak mereset database demo. Hasil dan batas verifikasi ada di [docs/TEST-RESULTS.md](docs/TEST-RESULTS.md).

## Alur utama

1. Supervisor membuat rencana dan penugasan; alat dan operator tidak boleh bentrok tanpa override beralasan.
2. Operator membuat draft, mengisi meter/waktu/aktivitas/BBM, mengunggah bukti lalu submit.
3. Supervisor mengembalikan dengan catatan atau memverifikasi; approver menyetujui. Dilarang approve laporan sendiri.
4. Laporan approved dibekukan. Pembukaan revisi beralasan hanya dapat dilakukan dari laporan terakhir dalam rantai meter; snapshot lama tetap tersimpan.
5. Breakdown dan work order maintenance diproses terpisah; release WO memerlukan hak khusus.
6. Admin import IW47 ke staging, review per baris, commit, lalu rekonsiliasi. Mapping sumber Actual Work bersifat eksplisit (meter/jam/volume). Tidak ada posting langsung ke SAP.
7. Laporan dapat diekspor Excel, CSV, PDF; ekspor besar dan import dapat diantrikan.

## Dokumentasi

- [Rencana implementasi dan ERD](docs/IMPLEMENTATION.md)
- [Data dictionary](docs/DATA-DICTIONARY.md)
- [Role dan permission](docs/ROLE-PERMISSIONS.md)
- [Alur proses](docs/FLOWS.md)
- [API](docs/API.md)
- [Panduan pengguna](docs/USER-GUIDE.md)
- [Deployment](docs/DEPLOYMENT.md)
- [Keamanan dan batas operasional](docs/SECURITY.md)
- [Template dan contoh IW47](docs/samples/)

PHP 7.4 dan Laravel 8 adalah stack lama. Audit dependensi masih menemukan advisory framework dan filesystem; mitigasi aplikasi dan batas produksi didokumentasikan pada panduan keamanan. Klaim production-ready tanpa review dan pengujian MySQL/staging tidak dibuat.

## UI EPITA

Tampilan profesional, login wave biru-putih, font Inter lokal, komponen Blade, motion ringan, reduced motion, dan QA responsif dijelaskan di [docs/UI-DESIGN.md](docs/UI-DESIGN.md). Build: `npm run build`; test motion: `npm run test:motion`.

Konfigurasi database lokal aktif: SQL Server 2022 Docker. Lihat [panduan SQL Server lokal](docs/SQL-SERVER-LOCAL.md). Jalankan Docker Desktop, lalu `./scripts/serve`.

## React Inertia dan mobile
Frontend web aktif memakai React + Inertia + Tailwind. Backend/API tetap PHP 7.4 Laravel 8 dengan SQL Server. Mobile React Native berada di `mobile/`, SQLite hanya lokal/offline.

- [Panduan integrasi dan build](docs/REACT-MOBILE-DELIVERY.md)
- [Kontrak sinkronisasi API](docs/SYNC-API.md)

`npm run build`, `npm run test:web`, `npm --prefix mobile test`, dan `npm --prefix mobile run export` adalah tooling frontend; tidak ada backend Node. Build mobile yang diekspor belum merupakan APK/IPA terpasang.

# epita-demo-
