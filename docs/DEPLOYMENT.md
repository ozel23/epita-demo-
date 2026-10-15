# Deployment PHP 7.4 / Laravel 8

1. Siapkan PHP 7.4.33 + extensions pada README, Composer, MySQL 8/MariaDB, Nginx/Apache, TLS, supervisor/systemd dan cron. Gunakan lingkungan terisolasi dan dukungan patch stack legacy sebelum produksi.
2. Deploy source; jalankan `composer install --no-dev --prefer-dist --optimize-autoloader` memakai PHP 7.4. Jangan mengubah platform ke PHP 8 atau mengabaikan platform requirements.
3. Isi `.env` dari `.env.example`: APP_ENV=production, APP_DEBUG=false, APP_URL HTTPS, DB user khusus least privilege, SMTP, SESSION_SECURE_COOKIE=true. Buat APP_KEY sekali dengan `php artisan key:generate`; jaga konsisten antar deploy.
4. `php artisan migrate --force`. **Jangan** jalankan DatabaseSeeder demo pada produksi; provision organisasi, permission/roles dan akun awal melalui seed provisioning terpisah yang ditinjau. `database/seeders/DatabaseSeeder.php` adalah referensi matriks, bukan seed produksi.
5. Pastikan storage dan bootstrap/cache writable oleh akun PHP; jangan beri izin 777. Semua upload privat di storage/app. `storage:link` hanya diperlukan bila nantinya ada aset publik; evidence tidak memerlukannya.
6. `php artisan config:cache`, `php artisan view:cache`, `php artisan route:cache`. Reload PHP-FPM dengan PHP 7.4.
7. Queue worker: `/usr/bin/php7.4 /srv/alat-vra/artisan queue:work database --queue=default --sleep=3 --tries=1 --timeout=300`. Jalankan dengan supervisor, restart otomatis, log privat. Restart worker setelah deploy dengan `php artisan queue:restart`.
8. Cron per menit: `* * * * * cd /srv/alat-vra && /usr/bin/php7.4 artisan schedule:run >> /var/log/alat-vra-scheduler.log 2>&1`. Scheduler mengirim overdue sekali per hari. Pantau failed_jobs/background_tasks dan disk usage.
9. Backup MySQL konsisten dan storage/app terenkripsi melalui infrastruktur. Uji restore pada staging. Simpan APP_KEY terpisah. Tidak ada endpoint backup publik.

Nginx contoh (sesuaikan socket):

```nginx
server {
 listen 443 ssl;
 server_name alat.example.com;
 root /srv/alat-vra/public;
 index index.php;
 client_max_body_size 12m;
 add_header X-Content-Type-Options nosniff always;
 location / { try_files $uri $uri/ /index.php?$query_string; }
 location = /index.php {
  include fastcgi_params;
  fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  fastcgi_pass unix:/run/php/php7.4-fpm.sock;
 }
 location ~ \.php$ { return 404; }
 location ~ /\. { deny all; }
}
```

Apache: document root hanya public/, mod_rewrite aktif, AllowOverride All untuk public/.htaccess. Jangan mengekspos root proyek. Konfigurasikan TLS dan PHP 7.4-FPM.

Sebelum go-live: jalankan suite pada MySQL staging; uji dua persetujuan/commit simultan, SMTP, worker nyata, restore backup, izin tiap role/plant dan unggahan file besar. Suite otomatis saat ini memakai SQLite; perilaku row locking dan konfigurasi infrastruktur memerlukan staging MySQL.

## Web React Inertia dan mobile
Web baru memakai `npm ci && npm run build` saat build/deploy. Layani `public/` melalui PHP-FPM 7.4 dengan pdo_sqlsrv/sqlsrv; root Inertia memuat aset ESM pada public/assets/react dan Tailwind CSS. Tidak ada SSR/Node server production. Laravel tetap menangani route, CSRF, session dan API. Setelah deploy jalankan migration, config/route/view cache, dan queue:restart memakai PHP dengan driver SQL Server.

Mobile: gunakan `mobile/`, `npm ci`, atur EXPO_PUBLIC_API_URL ke HTTPS Laravel, lalu build Android/iOS menggunakan toolchain platform. `npm run export` hanya bundle Hermes, bukan APK/IPA. iOS membutuhkan Xcode/signing Apple; Android membutuhkan JDK/Android SDK sesuai versi React Native. Jangan memasukkan kredensial SQL Server atau token tetap ke mobile/app.json atau source.

Sebelum produksi: APP_DEBUG=false, HTTPS, trust certificate SQL Server sesuai CA (bukan opsi localhost development), akun runtime SQL dengan hak minimum, uji restore backup SQL Server, kebijakan retensi receipt/offline queue, uji perangkat/mode pesawat, dan review advisori legacy PHP/Laravel. Lihat REACT-MOBILE-DELIVERY.md untuk batas verifikasi yang masih terbuka.
