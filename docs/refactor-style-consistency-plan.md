# Rencana Refactoring Konsistensi Style Antar Modul

## Tujuan

- Menyamakan style implementasi antar modul sejenis tanpa mengubah kontrak API, route, query param, payload, permission, atau `data-testid` yang sudah dipakai test.
- Menjalankan refactor dalam wave kecil yang bisa divalidasi per tahap dan per modul.
- Memanfaatkan abstraksi yang sudah ada lebih dulu, baru merapikan outlier yang belum mengikuti pola bersama.

## Landasan Saat Ini

- Frontend sudah punya pusat konfigurasi CRUD di `resources/js/utils/entityConfigs.ts` melalui `createSimpleEntityConfig()` dan `createComplexEntityConfig()`.
- Halaman report/read-only sudah punya shell bersama di `resources/js/components/common/ReportDataTablePage.tsx`.
- Halaman financial report sudah punya shell khusus di `resources/js/components/reports/financial/FinancialReportPageShell.tsx`.
- Registrasi route SPA terpusat di `resources/js/app-routes.tsx`.
- Depwire summary menunjukkan hotspot koneksi terbesar ada di model backend dan helper E2E, jadi urutan refactor sebaiknya dimulai dari surface shared frontend, request/resource helper, dan shell halaman sebelum masuk ke hotspot model.

## Guardrail Tetap

- Jangan ubah URI route, HTTP method, query param, response key, bentuk payload submit, atau permission name.
- Jangan ubah `data-testid` yang sudah ada.
- Semua command runtime project harus lewat `./vendor/bin/sail`.
- Jika perlu rename, move, split, merge, atau delete file, jalankan Depwire lebih dulu (`get_file_context`, `impact_analysis`, `simulate_change`) sebelum edit.
- Jika keputusan refactor bergantung pada perilaku library atau framework, ambil referensi terbaru via Context7 lebih dulu.
- Satu wave hanya boleh membawa satu shape refactor. Jangan campur simple CRUD, transaction form, dan report shell dalam wave yang sama.

## Aturan Klasifikasi Eksekusi

- Masuk bucket `CRUD Simple` bila implementasi aktual memakai pola `createSimpleEntityConfig`, `SimpleEntityForm`, `SimpleEntityViewModal`, dan tabel sederhana tanpa nested workflow berat.
- Masuk bucket `CRUD Complex` bila implementasi aktual memakai `createComplexEntityConfig` atau sibling file khusus seperti `Columns.tsx`, `Filters.tsx`, `Form.tsx`, `ViewModal.tsx`, termasuk modul dengan async select, nested items, atau dynamic arrays.
- Masuk bucket `Non-CRUD` bila halamannya berupa report read-only, dashboard, settings, approval/workflow page, atau embedded component yang bukan CRUD shell standar.
- Jika nama kategori di registry dan bentuk implementasi aktual berbeda, ikuti implementation surface yang ada di codebase saat ini.

## Target Style Per Jenis Modul

| Jenis Modul  | Anchor Existing                                                                                                                                                                       | Target Konsistensi                                                                                                                                                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CRUD Simple  | `resources/js/utils/entityConfigs.ts`                                                                                                                                                 | Page memakai `createSimpleEntityConfig()`, kolom sederhana reuse helper shared, form reuse `SimpleEntityForm`, view reuse `SimpleEntityViewModal`, request/resource/export backend mengikuti helper simple CRUD yang sama.                   |
| CRUD Complex | `resources/js/utils/entityConfigs.ts`, `resources/js/components/common/ItemFormDialog.tsx`, `resources/js/utils/schemas.ts`, `resources/js/components/common/ViewModalItemsTable.tsx` | Setiap modul mengikuti kontrak file yang konsisten (`Columns`, `Filters`, `Form`, `ViewModal`), reuse helper async select/item dialog/schema/error handling, dan backend memakai family pattern request/filter/resource/action yang sejenis. |
| Non-CRUD     | `resources/js/components/common/ReportDataTablePage.tsx`, `resources/js/components/reports/financial/FinancialReportPageShell.tsx`, `resources/js/layouts/app-layout.tsx`             | Read-only table/report memakai shell bersama, dashboard/settings/workflow memakai layout/loading/error state yang seragam, dan route SPA tetap diregistrasi dengan pola lazy route yang sama.                                                |

## Ukuran Wave

- Satu wave idealnya 2 sampai 3 modul atau maksimum 8 file produksi.
- Mulai dari anchor shared yang mengontrol behavior, lalu turunkan ke sibling module yang paling serupa.
- Selesaikan satu wave penuh sampai validasi selesai sebelum pindah tahap berikutnya.
- Dokumentasikan outlier yang sengaja belum dipindah agar tidak hilang di sesi berikutnya.

## Validasi Minimum Per Wave

- Frontend touched: `./vendor/bin/sail npm run types`
- PHP touched: `./vendor/bin/sail php ./vendor/bin/phpstan analyse <file...> --no-progress` bila scope cukup kecil, atau `./vendor/bin/sail test --group <module-slug>` bila ada group yang tepat
- E2E touched: `./vendor/bin/sail npm run test:e2e -- tests/e2e/<module-slug>/`
- Shared test helper atau banyak modul touched: `./vendor/bin/sail npm run test:e2e:smoke-waves`
- Handoff: update `task.md` setelah tiap wave selesai

## Tahap 0. Baseline dan Matriks Outlier

### Outcome

- Semua modul dipetakan ke bucket implementasi aktual: simple CRUD, complex CRUD, atau non-CRUD.
- Outlier per bucket terdokumentasi: mana yang belum mengikuti shell shared, helper shared, atau family request/resource yang sama.
- Urutan wave disepakati dari yang paling murah dan paling aman.

### Scope

- Audit dokumen dan implementation surface tanpa rename/move file.
- Mulai dari `docs/module-registry.md`, `resources/js/utils/entityConfigs.ts`, `resources/js/components/common/ReportDataTablePage.tsx`, `resources/js/components/reports/financial/FinancialReportPageShell.tsx`, dan `resources/js/app-routes.tsx`.

### Exit Criteria

- Ada daftar cohort nyata untuk Tahap 1 sampai Tahap 5.
- Modul yang secara domain tampak simple tetapi secara implementasi complex sudah ditandai eksplisit.
- Outlier yang butuh perubahan struktural sudah diberi catatan wajib Depwire.

### Prompt Eksekusi Tahap 0

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 0: baseline dan matriks outlier untuk konsistensi style antar modul.

Target:
- Petakan setiap modul ke bucket implementasi aktual: simple CRUD, complex CRUD, atau non-CRUD.
- Catat outlier yang belum mengikuti shell/config/helper shared.
- Jangan ubah kode produksi kecuali perlu menambah atau merapikan dokumentasi plan/handoff.

Aturan kerja:
- Mulai dari anchor shared: resources/js/utils/entityConfigs.ts, resources/js/components/common/ReportDataTablePage.tsx, resources/js/components/reports/financial/FinancialReportPageShell.tsx, resources/js/app-routes.tsx.
- Jangan lakukan broad repo exploration setelah bucket tiap modul sudah bisa ditentukan.
- Jika menemukan kandidat rename/move/split/merge untuk tahap berikutnya, catat dulu dan wajibkan Depwire pada eksekusi tahapnya.

Validasi:
- git diff --check -- docs/refactor-style-consistency-plan.md task.md

Handoff:
- Update task.md dengan hasil klasifikasi, outlier utama, dan next wave yang direkomendasikan.
```

## Tahap 1. Konvergensi CRUD Simple

### Outcome

- Modul simple CRUD memakai shell frontend dan backend yang benar-benar seragam.
- Perbedaan antar modul simple hanya tinggal metadata nama entitas, placeholder, dan permission yang memang spesifik.

### Wave Prioritas

- Wave 1A: `departments`, `positions`, `branches`
- Wave 1B: `customer-categories`, `supplier-categories`
- Wave 1C: modul lain yang setelah Tahap 0 terbukti masih memenuhi kontrak simple CRUD secara implementasi aktual

### Anchor

- `resources/js/utils/entityConfigs.ts`
- `resources/js/components/common/SimpleEntityForm.tsx`
- `resources/js/components/common/SimpleEntityViewModal.tsx`
- helper kolom/action/select shared yang dipakai tabel simple CRUD
- request/resource/export helper backend simple CRUD yang sudah ada di family request dan export

### Exit Criteria

- Page simple CRUD tidak lagi menyimpan custom shell yang bisa direduksi ke factory shared.
- Search placeholder, kolom select, action column, form, dan view modal konsisten antar sibling.
- Request/resource/export pair simple CRUD tidak lagi berbeda hanya karena boilerplate.

### Prompt Eksekusi Tahap 1

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 1: konvergensi CRUD Simple.

Batas wave:
- Maksimum 3 modul atau 8 file produksi.
- Jangan campur modul simple CRUD dengan complex/non-CRUD dalam wave yang sama.

Target wave ini:
- Samakan modul ke pola createSimpleEntityConfig() + SimpleEntityForm + SimpleEntityViewModal.
- Rapikan kolom select/actions/search/filter agar mengikuti helper shared yang sama.
- Rapikan request/resource/export/helper backend simple CRUD bila masih menyimpan boilerplate antar sibling.
- Jangan ubah route, endpoint, payload, query param, permission, atau data-testid.

Langkah wajib:
1. Mulai dari anchor shared, lalu turunkan ke sibling module wave ini.
2. Jika perlu perubahan struktural file, jalankan Depwire file context + impact analysis + simulate change sebelum edit.
3. Jika keputusan dipengaruhi behavior library, pakai Context7 dulu.
4. Setelah edit pertama, langsung jalankan validasi terfokus sebelum patch lain.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan modul yang sudah konvergen, validasi yang lulus, dan simple CRUD outlier yang tersisa.
```

## Tahap 2. Konvergensi CRUD Complex Master dan Detail Dasar

### Outcome

- Modul complex CRUD non-transaction mengikuti kontrak file dan helper yang sama.
- Variasi hanya tinggal field bisnis, filter khusus, dan view detail yang memang domain-specific.

### Wave Prioritas

- Wave 2A: `warehouses`, `customers`, `suppliers`
- Wave 2B: `employees`, `products`, `fiscal-years`, `coa-versions`
- Wave 2C: `account-mappings`, `journal-entries`, `asset-categories`, `asset-models`, `asset-locations`, `approval-delegations`, `pipelines`

### Anchor

- `resources/js/utils/entityConfigs.ts`
- `resources/js/utils/schemas.ts`
- `resources/js/utils/errorHandling.ts`
- `resources/js/components/common/ViewModalItemsTable.tsx`
- `resources/js/components/common` helper untuk async select, columns, modal detail, dan error mapping

### Exit Criteria

- Setiap modul complex dasar punya struktur sibling file yang seragam dan tidak lagi menyimpan boilerplate `Columns/Filters/Form/ViewModal` yang bisa diangkat ke helper shared.
- Family request, filter service, action, dan resource backend sudah konsisten per cohort.
- `createComplexEntityConfig()` menjadi titik masuk utama, bukan fallback terakhir.

### Prompt Eksekusi Tahap 2

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 2: konvergensi CRUD Complex master dan detail dasar.

Batas wave:
- Maksimum 2 sampai 3 modul yang benar-benar sekeluarga.
- Jangan campur modul transaction/nested-item ke wave ini.

Target wave ini:
- Pastikan modul memakai createComplexEntityConfig() dan struktur file sibling yang konsisten.
- Reuse helper shared untuk schema, async select, error handling, dan modal/view detail bila perilakunya sama.
- Rapikan pair request/filter service/resource/action backend yang masih berbeda hanya karena boilerplate.
- Jangan ubah route, payload, query param, atau data-testid.

Langkah wajib:
1. Mulai dari helper shared yang mengontrol behavior.
2. Jika perlu rename/move/split/merge, jalankan Depwire file context + impact analysis + simulate change dulu.
3. Jika behavior React, React Query, react-router-dom, zod, atau library lain perlu dipastikan, pakai Context7 sebelum edit.
4. Setelah edit pertama, langsung jalankan validasi terfokus.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan cohort complex CRUD yang sudah seragam dan outlier yang masih perlu helper baru.
```

## Tahap 3. Konvergensi CRUD Complex Transaction dan Nested Form

### Outcome

- Modul complex yang punya item dialog, nested rows, atau dynamic array berbagi helper form yang sama.
- Payload transaction dan nested resource tetap stabil, tetapi boilerplate UI dan request validation turun drastis.

### Wave Prioritas

- Wave 3A: `purchase-requests`, `purchase-orders`, `goods-receipts`, `supplier-returns`
- Wave 3B: `stock-transfers`, `inventory-stocktakes`, `stock-adjustments`
- Wave 3C: `assets`, `asset-movements`, `asset-maintenances`, `asset-stocktakes`, `approval-flows`

### Anchor

- `resources/js/components/common/ItemFormDialog.tsx`
- `resources/js/components/common/ViewModalItemsTable.tsx`
- `resources/js/utils/schemas.ts`
- helper modal detail dan section header/item action shared di `resources/js/components/common`
- action/resource/request backend untuk item collection dan nested payload

### Exit Criteria

- Boilerplate item dialog, item table, modal detail, dan nested schema sudah dipindah ke helper shared bila contract-nya sejenis.
- Tidak ada lagi clone block besar antar transaction form sibling hanya untuk field item, empty row, action buttons, atau error mapping.
- Nested payload dan export/import behavior tetap sama.

### Prompt Eksekusi Tahap 3

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 3: konvergensi CRUD Complex transaction dan nested form.

Batas wave:
- Maksimum 2 modul transaction sekeluarga atau 1 family helper shared dalam satu wave.
- Jangan campur report/read-only page ke wave ini.

Target wave ini:
- Reuse helper item dialog, item table, modal detail, schema shared, dan error handling shared untuk modul transaction/nested form.
- Rapikan request/resource/action backend yang mengelola item collection bila masih menyimpan mapping/validation boilerplate antar sibling.
- Pertahankan shape payload nested, upload/import/export flow, dan data-testid.

Langkah wajib:
1. Mulai dari helper shared atau satu transaction family yang paling serupa.
2. Jika ada perubahan struktural file, jalankan Depwire lebih dulu.
3. Jika ada behavior library yang belum pasti, pakai Context7.
4. Setelah edit pertama, jalankan validasi yang sama sebelum patch tambahan.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan family transaction yang sudah dipusatkan ke helper shared dan nested outlier yang masih tersisa.
```

## Tahap 4. Konvergensi Non-CRUD Report dan Read-Only Table

### Outcome

- Halaman report/read-only memakai shell, filter, kolom, dan export flow yang konsisten.
- Perbedaan antar report tinggal query business logic dan presentasi domain-specific yang memang harus berbeda.

### Wave Prioritas

- Wave 4A: `stock-movements`, `stock-monitor`, `inventory-valuation-report`, `stock-movement-report`, `stock-adjustment-report`
- Wave 4B: `purchase-order-status-report`, `purchase-history-report`, `goods-receipt-report`
- Wave 4C: `asset-reports/register`, `book-value-depreciation-reports`, `maintenance-cost-reports`, `asset-stocktake-variances`, `pipeline-audit-trail`, `approval-audit-trail`
- Wave 4D: financial report pages (`balance-sheet`, `comparative`, `income-statement`, `cash-flow`, `trial-balance`) bila masih ada drift terhadap shell shared

### Anchor

- `resources/js/components/common/ReportDataTablePage.tsx`
- `resources/js/components/common/ReportColumns.tsx`
- `resources/js/components/reports/financial/FinancialReportPageShell.tsx`
- helper filter report shared di `resources/js/components/common` atau `resources/js/components/reports`
- request/resource/export/action backend family report

### Exit Criteria

- Report table wrapper tipis memakai `ReportDataTablePage` bila pola interaction-nya sama.
- Financial pages yang sejenis memakai `FinancialReportPageShell` atau helper shared terkait.
- Kolom/filter/export/report resource tidak lagi menyimpan clone block yang seharusnya menjadi helper shared.

### Prompt Eksekusi Tahap 4

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 4: konvergensi Non-CRUD report dan read-only table.

Batas wave:
- Maksimum 2 sampai 3 report sibling yang memakai shell interaksi yang sama.
- Jangan campur dashboard/settings/workflow page ke wave ini.

Target wave ini:
- Samakan page wrapper ke ReportDataTablePage atau FinancialReportPageShell bila cocok.
- Reuse helper report columns, filters, export flow, dan resource/request pair backend bila kontraknya sejenis.
- Pertahankan endpoint, query param filter, response key, dan route report yang ada.

Langkah wajib:
1. Mulai dari shell report shared, baru turunkan ke sibling report.
2. Jika ada perubahan struktural file, jalankan Depwire terlebih dahulu.
3. Jika behavior package/report helper perlu dipastikan, pakai Context7 sebelum edit.
4. Jalankan validasi terfokus segera setelah edit pertama.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group reports
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan report family yang sudah seragam dan report outlier yang tetap butuh shell khusus.
```

## Tahap 5. Konvergensi Non-CRUD Workflow, Dashboard, dan Settings

### Outcome

- Halaman workflow/dashboard/settings memakai pola layout, loading, empty-state, error mapping, dan route registration yang konsisten.
- Embedded component tetap mempertahankan contract-nya, tetapi tidak lagi mengulang boilerplate page shell.

### Wave Prioritas

- Wave 5A: `admin-settings`, `my-approvals`, `approval-monitoring`
- Wave 5B: `pipeline-dashboard`, `asset-dashboard`, `asset-depreciation-runs`
- Wave 5C: `approval-history`, `entity-state-actions`, `entity-state-timeline`

### Anchor

- `resources/js/layouts/app-layout.tsx`
- `resources/js/app-routes.tsx`
- `resources/js/utils/errorHandling.ts`
- shared cards/filter/summary components di `resources/js/components/common`, `resources/js/components/approval-monitoring`, dan family dashboard terkait

### Exit Criteria

- Workflow/dashboard/settings pages tidak lagi punya boilerplate error handling, loading, atau layout registration yang berbeda tanpa alasan bisnis.
- Route lazy registration tetap konsisten di `app-routes.tsx`.
- Embedded approval/entity state components tetap kompatibel dengan caller yang ada.

### Prompt Eksekusi Tahap 5

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 5: konvergensi Non-CRUD workflow, dashboard, dan settings.

Batas wave:
- Maksimum 2 sampai 3 page/component sibling.
- Jangan campur report/read-only table ke wave ini.

Target wave ini:
- Samakan layout, loading, empty-state, error handling, dan route registration untuk page workflow/dashboard/settings.
- Reuse helper shared yang sudah ada di AppLayout, errorHandling, dan summary/filter component.
- Pertahankan permission gate, endpoint, payload, dan kontrak embedded component.

Langkah wajib:
1. Mulai dari shared layout/helper yang sudah mengontrol behavior.
2. Jika perlu perubahan struktural, jalankan Depwire file context + impact analysis + simulate change sebelum edit.
3. Jika behavior library perlu dikonfirmasi, pakai Context7.
4. Jalankan validasi terfokus segera setelah edit pertama.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan page workflow/dashboard/settings yang sudah seragam dan exception yang masih disengaja.
```

## Tahap 6. Hardening Test, Registry, dan Handoff

### Outcome

- Registry test, shared factory, dan handoff selaras dengan bentuk implementasi akhir.
- Sisa outlier tercatat jelas sebagai intentional exception atau backlog lanjutan.

### Scope

- `docs/module-registry.md`
- `tests/e2e/shared-test-factories.ts` dan helper test terkait
- `task.md`
- `docs/refactor-sonar-progress.md` bila wave memang memakai tracking Sonar

### Exit Criteria

- Registry modul mencerminkan implementation surface terbaru.
- Shared test helper mendukung style final tiap cohort.
- Handoff sudah cukup untuk melanjutkan wave berikutnya tanpa re-triage besar.

### Prompt Eksekusi Tahap 6

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 6: hardening test, registry, dan handoff setelah wave refactor style.

Target:
- Selaraskan docs/module-registry.md dengan implementation surface terbaru.
- Rapikan shared E2E/Pest helper bila ada perubahan pola yang sekarang sudah menjadi standar.
- Update task.md dan, jika relevan untuk wave Sonar, docs/refactor-sonar-progress.md.
- Jangan membuka scope baru ke modul produksi kecuali diperlukan untuk menutup mismatch dokumentasi atau helper test.

Langkah wajib:
1. Mulai dari helper test dan dokumen registry, bukan dari broad repo search.
2. Jika perubahan file structure helper diperlukan, jalankan Depwire dulu.
3. Setelah edit pertama, jalankan validasi terfokus yang paling kecil.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika shared frontend/test helper berubah lintas banyak modul: ./vendor/bin/sail npm run test:e2e:smoke-waves

Handoff:
- Tutup sesi dengan update task.md yang memuat wave terakhir, validasi, blocker, dan next step paling kecil.
```

## Urutan Eksekusi yang Direkomendasikan

1. Tahap 0 untuk memetakan bucket implementasi aktual dan outlier.
2. Tahap 1 untuk membersihkan simple CRUD karena paling murah dan paling deterministik.
3. Tahap 2 untuk complex CRUD dasar yang belum punya nested transaction weight tinggi.
4. Tahap 3 untuk family transaction dan nested form yang paling rawan duplikasi.
5. Tahap 4 untuk report/read-only shell.
6. Tahap 5 untuk workflow, dashboard, dan settings.
7. Tahap 6 untuk registry, shared tests, dan handoff.

## Definition of Done Keseluruhan

- Modul sejenis memakai anchor shared yang sama dan hanya berbeda pada metadata/domain logic.
- Tidak ada perbedaan style yang tersisa hanya karena historis copy-paste.
- Semua perubahan tervalidasi per wave dengan command terfokus.
- `task.md` selalu cukup untuk melanjutkan wave berikutnya tanpa mengulang triage besar.
