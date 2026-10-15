# REST API v1

Prefix `/api/v1`. Login menghasilkan token Sanctum. Kirim `Authorization: Bearer <token>` dan `Accept: application/json`; pengguna nonaktif atau sesi yang dicabut ditolak. Rate limit umum 60 request/menit/pengguna, login 5/menit/IP. Hak akses dan scope plant sama dengan web.

| Method | Path | Tujuan |
|---|---|---|
| POST | /login | login, password, device_name |
| POST | /logout | Cabut token saat ini |
| GET | /me | Identitas, peran, plant |
| GET | /dashboard | KPI/filter from,to,plant_id,company_id,division_id,equipment_type_id,ownership |
| GET | /assignments | Penugasan sendiri atau seluruh scope untuk supervisor |
| GET | /equipment | Alat aktif dalam scope |
| GET | /master-data | Referensi yang diizinkan |
| GET | /daily-reports | Filter status,from,to,equipment_id; pagination |
| POST | /daily-reports | Buat draft |
| GET | /daily-reports/{id} | Detail dan child records |
| PUT | /daily-reports/{id} | Perbarui DRAFT/RETURNED milik sendiri |
| POST | /daily-reports/{id}/submit | Bekukan snapshot dan kirim |
| POST | /daily-reports/{id}/attachments | multipart file, gambar maks 5 MB |
| POST | /breakdowns | multipart foto file wajib |
| GET | /notifications | Notifikasi pengguna |

Single response: `{ "data": {...} }`. Pagination: `{ "data": [...], "meta": { "current_page": 1, "per_page": 15, "total": 20, ... } }`. Validation HTTP 422: `{ "message": "...", "errors": { "meter_end": ["..."] } }`. Auth 401, authorization 403, missing/scoped reference 404, invalid workflow 422. Repeat submit returns 422 once state changes; clients should read status before retry. Upload retries can add another attachment; clients should avoid automatic blind retries.

## Contoh draft

```json
{
  "plant_id": 1,
  "equipment_id": 1,
  "main_operator_id": 1,
  "location_id": 1,
  "report_date": "2026-10-14",
  "shift": "1",
  "meter_start": 1329,
  "meter_end": 1368,
  "work_start_at": "2026-10-14 07:00:00",
  "work_end_at": "2026-10-14 16:00:00",
  "equipment_condition": "WORKING",
  "activities": [{"activity_code_id":1,"work_unit_id":2,"volume":1000}],
  "helpers": [],
  "fuels": [{"fuel_type_id":1,"quantity_filled":20,"quantity_used":15,"quantity_remaining":5}]
}
```

IDs dan meter awal harus dibaca dari master/alat aktual; contoh bukan payload produksi. Total meter, durasi dan rasio selalu dihitung backend. Untuk lintas tengah malam, work_end_at menggunakan tanggal berikutnya (maks 24 jam). Volume produktif harus positif; total meter nol memerlukan non_moving_reason_id. Assignment opsional harus cocok plant/alat/operator/interval.

## Breakdown

Field: plant_id,equipment_id,location_id,occurred_at,meter,symptom,severity (`LOW|MEDIUM|HIGH|CRITICAL`),description,file. Foto: jpg/jpeg/png/webp maks 5 MB.

API mobile/offline, idempotency, UUID, versioning, cursor, dan upload private terbaru dijelaskan pada [SYNC-API.md](SYNC-API.md). Semua endpoint tetap Laravel /api/v1.
