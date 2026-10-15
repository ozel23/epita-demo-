# Kontrak API mobile EPITA

Semua endpoint berada di Laravel /api/v1. Authorization: Bearer TOKEN dari POST login (login, password, device_name). Logout mencabut token saat ini. Sanctum expiry default 1440 menit, dapat diatur SANCTUM_EXPIRATION. Token dan password tidak boleh dilog.

Response JSON: data untuk sukses, message/errors untuk validasi; pagination data + meta. Rate limit login 5/menit dan middleware throttle API yang ada. Datetime database disimpan konsisten dalam waktu bisnis Asia/Jakarta (kolom SQL tanpa offset); API menserialisasi datetime model menjadi ISO 8601 UTC. Input ber-offset dinormalisasi Laravel ke Asia/Jakarta sebelum validasi tanggal, bentrok, dan penyimpanan.

Mutation daily create/update, submit, attachment, dan breakdown memerlukan header Idempotency-Key 16–80 karakter [A-Za-z0-9_-]. Key yang sama harus memakai route/method/payload/file yang sama. Replay menghasilkan response asli; payload berbeda 409. Login/logout dan sync mempunyai kontrak sendiri.

## Sync endpoints
- GET /sync/bootstrap: profile, permissions, master, equipment, cursor, server_time.
- GET /sync/pull?cursor=...: reports (termasuk rincian), cursor baru, has_more, server_time; maksimum 100 event jurnal per halaman. Record yang sama dapat muncul lagi, sehingga upsert berdasarkan UUID/version.
- POST /sync/push: client_batch_id UUID, items maksimum 50. Setiap item: client_uuid UUID, server_version integer (0 untuk baru), operation save/submit, data sesuai DailyReportRequest.
- POST /sync/acknowledge: device_id, cursor.
- GET /sync/status/{client_batch_id}: receipt hanya untuk pemilik.

```json
{"client_batch_id":"fead28d7-b8f0-4f96-9e8f-a8d41a31e418","items":[{"client_uuid":"0d32f568-1845-44d9-acbc-f3509a07a749","server_version":0,"operation":"save","data":{"plant_id":1,"equipment_id":1,"main_operator_id":1,"location_id":1,"report_date":"2026-10-15","shift":"1","meter_start":1000,"meter_end":1030,"work_start_at":"2026-10-15T07:00:00+07:00","work_end_at":"2026-10-15T10:00:00+07:00","equipment_condition":"WORKING","activities":[{"activity_code_id":1,"work_unit_id":2,"volume":100}],"fuels":[]}}]}
```
ID pada contoh hanya ilustrasi; gunakan ID hasil bootstrap. Jangan mengasumsikan ID selalu dimulai dari 1.

Hasil per item: SYNCED + server, FAILED + errors/status/message, atau CONFLICT + snapshot server. Versi server selalu integer. Approved/verified/submitted tidak dapat di-overwrite sync. Submit tanpa bukti wajib gagal sesuai rule PHP; klien harus save → upload → submit.

GET /daily-reports, GET /daily-reports/{id}, GET /assignments, GET /equipment, GET /master-data, GET /notifications tersedia dengan plant scope. Attachment download melalui GET /attachments/{id} memakai bearer token dan policy laporan. Upload multipart POST /daily-reports/{id}/attachments memakai file jpg/jpeg/png/webp maksimum 5 MB. Breakdown multipart POST /breakdowns juga wajib file foto. File disimpan private, bukan public URL.
