---
name: Feature Custom / Non-CRUD
description: Workflow untuk fitur non-standar seperti Dashboard, Settings, atau Halaman Khusus.
---

# Feature Custom / Non-CRUD

Gunakan skill ini untuk membuat halaman atau fitur yang tidak mengikuti pola CRUD standar (misalnya: Dashboard, Report, Permissions Matrix, User Settings).

## 1. Kriteria
- Tidak ada resource `create/edit` standar.
- Mungkin hanya `index` (Dashboard) atau `update` (Settings).
- Routing custom (tidak pakai `Route::resource`).

## 2. Strategi Backend
- **Controller**: Bisa `Single Action Controller` (`__invoke`) jika hanya satu fungsi.
- **Service**: Gunakan Service Class jika logic pengolahan data rumit (misal: `ReportService`).
- **Query**: Hati-hati dengan N+1 problem saat load data untuk dashboard.

## 3. Strategi Frontend
- **Layout**: Apakah perlu layout khusus?
- **Components**: Buat komponen spesifik fitur di `resources/js/components/{feature}`.
- **Charts/Widgets**: Jika dashboard, pastikan performa rendering.

## 4. Langkah Implementasi
1.  **Route Definition**: Tentukan URL di `routes/web.php`.
2.  **Controller**: Buat logic pengambilan data.
3.  **Frontend**: Buat UI sesuai mockup/kebutuhan.
4.  **Tests**:
    - Fokus pada **Smoke Testing**: Pastikan halaman bisa dibuka (Status 200).
    - Tes interaksi kunci (misal: ganti filter report).

## 5. Verification
- [ ] Manual Check di browser.
- [ ] Smoke Test (Feature Test).
