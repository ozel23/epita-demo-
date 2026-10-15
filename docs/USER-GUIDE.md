# Panduan per peran

Setelah masuk, dashboard langsung tersedia. Gunakan pemilih plant di topbar agar pilihan data lebih ringkas. Semua tanggal/jam menggunakan WIB.

## Operator

Buka Penugasan untuk melihat tugas Anda. Pilih Laporan Harian → Laporan Baru. Isi identitas, meter/waktu (termasuk tanggal selesai bila lintas malam), aktivitas/volume, BBM, kondisi dan catatan. Form menyimpan salinan lokal di browser; **Simpan Draft** menyimpan ke database. Data lokal browser tidak menggantikan penyimpanan server. Setelah tersimpan, unggah bukti pada detail laporan lalu pilih Kirim. Laporan rusak atau aktivitas dengan aturan foto tidak bisa dikirim tanpa bukti. Perbaiki RETURNED sesuai catatan lalu kirim ulang. Jangan membagikan perangkat dengan draft yang belum disimpan; logout setelah selesai.

## Supervisor / Mandor

Buat rencana dengan target volume dan satuan; buat penugasan alat/operator dan interval waktu. Sistem menolak bentrok; override membutuhkan izin dan alasan. Buka Persetujuan, cek detail meter, hasil pekerjaan, BBM dan foto. Kembalikan dengan catatan bila salah, Verifikasi bila benar, lalu Setujui sesuai izin. Anda tidak dapat menyetujui laporan sendiri. Buka Revisi dengan alasan jika laporan approved terakhir harus diperbaiki; histori snapshot tetap tersedia.

## Admin / Kerani

Kelola Master Data sesuai scope, tambah pengguna dan pilih role/plant. Admin tidak mendapat hak approval otomatis. Gunakan Import IW47 → Template, isi file, unggah, periksa error per baris. Batch harus bebas error untuk commit. Perbaikan file dilakukan sebagai batch baru; rollback mempertahankan raw staging. Sesuaikan mapping order/operation/unit/sumber nilai, lalu jalankan rekonsiliasi. Review exception dengan catatan. Unduh laporan sesuai filter melalui Excel/CSV/PDF. Antrean import/export membutuhkan worker aktif; hasil privat tersedia di Proses Latar Belakang.

## Maintenance

Buka Breakdown atau buat laporan dengan foto, meter, gejala, severity dan lokasi. Buat WO, pilih breakdown/alat/teknisi. Mulai WO mengubah kondisi menjadi MAINTENANCE. Catat tindakan, tenaga dan penggunaan part. Isi diagnosis dan hasil testing untuk menyelesaikan pekerjaan. User dengan maintenance.close menutup WO dan memilih STANDBY/WORKING; sistem menghitung downtime hingga release. Preventive dipisahkan dari unplanned downtime.

## Manager / Asisten Estate

Gunakan filter dashboard, laporan utilisasi, rencana versus realisasi, BBM dan downtime. Approval final tersedia pada laporan VERIFIED sesuai izin dan scope. Data teknis tidak dapat diedit tanpa hak tambahan.

## Pengelolaan akun

Profil dapat memperbarui nama/kontak dan password. Pemulihan password memerlukan konfigurasi SMTP; lokal memakai log mail. Nonaktifkan akun yang tidak digunakan dari Pengguna & Akses; token dan sesi lama dicabut melalui versi keamanan. Role seed dapat ditinjau pada Matriks Peran.

## Mobile offline
Login online pertama untuk mengunduh akses, plant, master, dan tugas. Pilih plant aktif. Isi laporan, simpan draft, tambahkan foto, lalu pilih Kirim saat koneksi tersedia. Pusat sinkronisasi menampilkan queued/failed/conflict. Koneksi terputus akan di-retry; jangan membuat laporan baru untuk transaksi yang sama hanya karena jaringan lambat. Jika konflik, periksa lokal dan server; status submitted/verified/approved selalu mengikuti server. Pilih Tinjau draft lokal hanya jika server masih draft/returned milik Anda. Logout menghapus cache/draft/foto perangkat setelah konfirmasi; sinkronkan dahulu bila masih diperlukan.
