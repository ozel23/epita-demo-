# Diagram workflow

```mermaid
flowchart TD
 P[Rencana kerja] --> A[Penugasan alat/operator]
 A --> D[Draft laporan operator]
 D --> S[SUBMITTED + snapshot]
 S --> V[VERIFIED]
 S --> R[RETURNED + catatan]
 V --> R
 R --> D
 V --> F[APPROVED + meter terakhir + audit]
 F --> E[Rekap / ekspor]
 F --> X[Revisi beralasan dari laporan terakhir]
 X --> R
```

```mermaid
flowchart LR
 B[Breakdown REPORTED] --> O[WO OPEN]
 O --> I[WO IN_PROGRESS / alat MAINTENANCE]
 I --> T[Tindakan + part + diagnosis + testing]
 T --> C[COMPLETED]
 C --> K[CLOSED / release STANDBY atau WORKING]
 K --> H[Histori kondisi + downtime]
```

```mermaid
flowchart TD
 U[Upload CSV/XLSX] --> S[Staging raw + normalized JSON]
 S --> V[Validasi tanggal, plant, equipment, confirmation]
 V --> ER[Error/duplicate: review dan batch baru]
 V --> C[Commit batch bebas error]
 C --> M[Mapping order, operation, unit, sumber nilai]
 M --> R[Reconcile approved local reports]
 R --> X[MATCHED / VALUE_MISMATCH / DATE_MISMATCH]
 R --> Y[MAPPING_MISSING / ONLY_LOCAL / ONLY_SAP / DUPLICATE]
 X --> E[Review + export]
 Y --> E
 C --> RB[Rollback: raw staging dipertahankan]
```

Actual Work SAP tidak otomatis sama dengan jam kerja. Rule menentukan meter_total, work_hours atau volume dan unit yang disetujui. Approval mengunci equipment dan laporan dalam transaction. Revision snapshot tidak ditimpa. Maintenance tidak memakai tabel daily report.
