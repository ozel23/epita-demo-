# EPITA — UI dan motion system

Implementasi dari dokumen UI profesional pada 14 Oktober 2026. Runtime backend tetap Laravel 8/PHP 7.4.33. Perubahan bisnis terbatas pada query pembanding periode KPI, indikator WO lebih dari 24 jam, dan pembacaan timeline audit maintenance; izin, approval, scope plant, dan perhitungan transaksi tetap memakai service/policy yang ada.

## Identitas dan sistem visual

- Identitas EPITA dengan logo SVG gear, daun, dan kendaraan yang dapat diganti pada `resources/views/components/brand.blade.php`.
- Inter lokal, empat weight 400/500/600/700, font-display swap, preload weight utama. Tidak memakai Google Fonts atau library ikon jarak jauh.
- Palet biru/navy, canvas terang, aksen kuning untuk tindakan utama, radius dan spacing konsisten, fokus keyboard terlihat.
- Login dua panel 44/56%, pembatas wave SVG berlapis, form satu kolom pada mobile, field/tombol 48 px, pesan inline, show/hide password, loading submission.
- Sidebar gradient dan active state semua halaman, collapse tersimpan lokal, offcanvas mobile dengan focus trap/Escape dan backdrop, profile dropdown, notification badge.
- Dashboard: sapaan WIB/peran/plant/tanggal, attention strip, KPI dan pembanding periode dari database, grafik lazy, utilisasi numerik/ring, progres rencana/realisasi, pencarian/pagination aktivitas serta card view mobile.
- Tabel dan form modul lain mengikuti stylesheet bersama. Detail laporan memakai approval stepper/timeline; maintenance menampilkan event audit. Halaman 403/404/419/500/503 memakai identitas yang sama.
- Draft recovery menjadi banner nonblocking. Multipart upload mempunyai preview, hapus pilihan file, progress transfer nyata, dan error yang tetap terlihat.
- Konfirmasi status menggunakan dialog native dengan nama objek; aksi arsip/rollback memakai warna merah. Tombol submit mempertahankan nilai named button dan tidak memblokir download export.

## Komponen Blade

`resources/views/components/`: brand, icon, button, input, select, date-time, badge, stat-card, chart-card, data-table, modal, drawer, alert, toast, breadcrumb, empty-state, skeleton, confirmation-dialog, timeline, file-upload, approval-stepper.

Semua ikon outline dari satu set SVG internal, tanpa raster atau dependensi runtime tambahan. `resources/css/app.css` adalah sumber stylesheet; jangan mengedit file minified di public/assets secara langsung.

## Motion dan ketahanan

Token: fast 160 ms, normal 240 ms, slow 480 ms; easing cubic-bezier(.2,.8,.2,1). Entrance card login 560 ms, stagger KPI 65 ms, chart 620 ms. Gradient/wave/particle dibatasi dua siklus, bukan animasi dekoratif tanpa batas. Counter hanya sekali pada tampilan awal, final number tetap ada pada HTML dan screen reader. Sidebar resize responsif tidak menjalankan animasi margin; collapse hanya saat pengguna meminta.

- CSS `prefers-reduced-motion` mematikan entrance, wave/gradient, smooth scroll, hover transform dan transisi.
- `motion.js` terpisah dari `app.js`. Jika file animasi tidak termuat, menu, form, angka akhir dan navigasi tetap tersedia.
- Chart.js hanya dimuat pada dashboard; chart di bawah fold dibuat melalui IntersectionObserver. Perubahan preferensi reduced-motion menghentikan chart.
- Teks/error tidak berkedip. Skeleton berhenti setelah beberapa siklus; chart yang gagal tidak menyembunyikan KPI atau tabel.
- Ada fallback noscript untuk navigasi dan penjelasan grafik. Validasi, submission, data dan permission berada di server.

## Build dan verifikasi

```sh
npm ci
npm run build
npm run test:motion
./scripts/php74 artisan view:cache
./scripts/php74 vendor/bin/phpunit --testdox
```

Build esbuild meminifikasi CSS/JavaScript, menyalin font beserta lisensi, Bootstrap dan Chart.js. URL stylesheet/script memakai versi filemtime. Tidak diperlukan migration atau seeder baru untuk perubahan UI.

Hasil akhir terdapat pada `siova-ui-phpunit-results.txt`: **40 test, 162 assertion**, PHP 7.4.33. Test tambahan memeriksa nilai pembanding KPI terhadap periode sebelumnya dan timeline maintenance. `scripts/test-motion.mjs` memeriksa mode reduced motion, angka final, jumlah frame counter yang terbatas, dan chart statis/durasi normal 620 ms. Semua 65 compiled Blade files juga diperiksa dengan parser PHP 7.4.

Browser QA pada lebar **360, 768, 1024, 1366, 1920**: 75 kombinasi halaman/viewport tanpa horizontal scroll. Halaman: login, dashboard, laporan harian, form laporan, daftar WO, reports, profil, master equipment, form equipment, users, roles, SAP import, reconciliation, audit dan background tasks. Hasil mentah: `siova-responsive-results.json`. Pagination audit dan intrinsic canvas width diperbaiki dari temuan QA.

Interaksi yang diperiksa: login supervisor dan admin, show/hide password, search dan pagination aktivitas, offcanvas mobile, profile dropdown, dialog approval yang dibatalkan dengan Escape (status tidak diubah), focus kembali ke tombol pemicu, dan timeline maintenance. Screenshot ada di `screenshots/siova/`.

Batas verifikasi: skor Lighthouse Performance 85/Accessibility 90 merupakan target, **belum diukur dan tidak diklaim tercapai**. Reduced-motion diverifikasi melalui unit harness dan CSS; browser dengan JavaScript sepenuhnya dimatikan belum dijalankan. Fallback tanpa animasi diaudit melalui HTML server-rendered dan pemisahan file motion. Uji upload progress/error melalui harness jaringan lambat serta perangkat fisik tetap dianjurkan sebelum rilis.
