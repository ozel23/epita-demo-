# Security review dan batas operasional

Target PHP 7.4 dan Laravel 8 dipertahankan sesuai permintaan. Stack ini telah melewati dukungan upstream; diperlukan dukungan patch legacy/isolasi dan review deployment sebelum produksi.

Audit Composer 14 Oktober 2026 melaporkan lima advisory pada Laravel Framework dan League Flysystem (lihat `dependency-audit.json`). Framework tidak dapat dinaikkan ke rilis patched terkini tanpa mengubah kebutuhan Laravel 8/PHP 7.4. Tidak ada advisory yang disembunyikan atau dimatikan dalam konfigurasi Composer.

Mitigasi aplikasi:
- APP_DEBUG=false pada deployment; semua output Blade escaped.
- Download evidence/export melalui autentikasi dan scope record, tanpa signed URL.
- File foto scalar, ekstensi, MIME, ukuran dan decoding image diperiksa; nama storage acak, disk privat. Thumbnail dari image decoded.
- Email pengguna menolak CR/LF; password dan token tidak masuk audit log.
- Raw path tidak berasal dari nama file pengguna; file import/export menggunakan nama acak.
- CSRF web, Sanctum API, throttle login/API, password hash, akun nonaktif ditolak.
- Approval/transisi dalam transaction, equipment/report/plant lock dan validasi status eksplisit; approver tidak boleh pembuat sendiri.
- Scope plant diterapkan pada baca/tulis/export/download dan diperiksa ulang dalam job.
- Role assignment memerlukan role.assign. Admin tidak punya izin approval langsung. Pengelolaan akun adalah hak sensitif yang harus dibatasi.
- Versi keamanan akun mencabut sesi lama setelah password/nonaktif; tokens dihapus. Local draft hanya browser pengguna, jangan digunakan pada perangkat bersama tanpa pembersihan.

Batas implementasi yang perlu diketahui:
- Verifikasi otomatis database menggunakan SQLite; uji locking MySQL concurrency dilakukan pada staging sebelum go-live.
- Import dibatasi 10 MB/10.000 baris, chunk 500; antrean bukan alasan untuk menerima file tak terbatas.
- Rekonsiliasi konservatif: lebih dari satu laporan alat pada tanggal sama memerlukan review DUPLICATE; kecocokan tanggal terdekat maksimum satu hari menghasilkan DATE_MISMATCH. Mapping per plant/activity menentukan order, operation, unit dan sumber nilai.
- Upload belum memakai antivirus; terapkan pemindaian infrastruktur bila dibutuhkan kebijakan perusahaan.
- Tanda tangan digital kriptografis, SAP direct posting dan backup UI tidak diaktifkan. PDF mencantumkan approver/waktu dan ruang tanda tangan.
- Durasi dibatasi 24 jam, utilisasi menggunakan jam kalender 24 jam/hari; sesuaikan denominator shift kalender bila diperlukan aturan bisnis berbeda.
- Master global (activity/unit/fuel/type) bersifat bersama; batasi equipment.manage pada pengelola master pusat. Plant master tidak dapat dipindahkan setelah dibuat agar histori aman.
- Data dictionary dan flow menggambarkan implementasi, bukan sertifikasi produksi.
