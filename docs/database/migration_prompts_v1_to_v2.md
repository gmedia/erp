# Rekomendasi Prompt: Migrasi Database V1 → V2

> **Cara pakai:** Copy-paste prompt di bawah ini saat memulai sesi baru. Urutan eksekusi mengikuti nomor fase. Setiap prompt dirancang untuk 1 sesi kerja.

---

## Fase 1: Database Migration

```
Buat migration baru untuk menyesuaikan schema Products ke desain V2.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md`
- Desain target: `docs/database/00_products_design_v2.md`
- Migration existing: `database/migrations/2026_01_29_114413_create_products_table.php` dan file terkait (114414-114422)

Instruksi:
1. Baca `docs/database/migration_plan_v1_to_v2.md` bagian "Detail Gap: Products Module"
2. Buat satu file migration: `2026_02_19_000001_align_products_to_v2.php`
3. Isi migration berdasarkan gap analysis:
   - Rename kolom (gunakan `$table->renameColumn()`)
   - Add kolom baru
   - Alter tipe data (integer → decimal)
   - Alter nullable
   - Drop kolom ekstra (HANYA jika disetujui user)
   - Update indexes & constraints
4. Pastikan method `down()` bisa rollback semua perubahan
5. JANGAN jalankan migration dulu — tunggu review

Output: 1 file migration baru
```

---

## Fase 2: Model Updates

```
Update Eloquent Models agar sesuai dengan schema V2 setelah migration dijalankan.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md` bagian "Fase 2: Model Updates"
- Migration baru: `database/migrations/2026_02_19_000001_align_products_to_v2.php`
- Desain V2: `docs/database/00_products_design_v2.md`

Instruksi:
1. Baca migration baru untuk tahu perubahan schema yang pasti
2. Update model-model berikut sesuai perubahan kolom:
   - `app/Models/Product.php` — `category_id` → `product_category_id`, update $fillable, $casts, relations
   - `app/Models/BillOfMaterial.php` — `quantity_required` → `quantity`, tambah `waste_percentage`
   - `app/Models/ProductionOrder.php` — rename columns, tambah relations baru
   - `app/Models/ProductionOrderItem.php` — `raw_material_id` → `product_id`
   - `app/Models/ProductDependency.php` — `required_product_id` → `related_product_id`, `dependency_type` → `type`
   - `app/Models/ProductStock.php` — update casts decimal
   - `app/Models/ProductPrice.php` — nullable customer_category_id
3. Pastikan semua relationship methods tetap konsisten

Output: Updated model files
```

---

## Fase 3: Backend (Controller, Request, Resource, Action)

```
Update backend layer (Controller, FormRequest, Resource, Action) agar sesuai setelah model updates.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md`
- Model yang sudah di-update di Fase 2

Instruksi:
1. Cari semua referensi ke kolom lama menggunakan grep:
   - `category_id` (tanpa prefix `product_`) di konteks products
   - `quantity_required` di konteks BOM
   - `raw_material_id` di konteks production_order_items
   - `required_product_id` di konteks product_dependencies
   - `dependency_type` di konteks product_dependencies
   - `quantity_to_produce`, `production_date`, `completion_date` di konteks production_orders
2. Update semua file yang ditemukan:
   - Controllers: query, validation, response
   - FormRequests: rules, messages
   - Resources: toArray()
   - Actions: business logic
3. Jangan update tests dulu — itu Fase 6

Output: Updated backend files
Verifikasi: Jalankan `./vendor/bin/sail test --group products` (expect beberapa test gagal karena factory belum di-update)
```

---

## Fase 4: Frontend Updates

```
Update frontend React/Inertia components agar sesuai dengan perubahan kolom backend.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md`
- Perubahan backend dari Fase 3

Instruksi:
1. Cari semua referensi ke kolom lama di `resources/js/`:
   - `category_id` → `product_category_id`
   - `quantity_required` → `quantity`
   - `dependency_type` → `type`
   - (kolom lain yang di-rename)
2. Update file-file di:
   - `resources/js/pages/products/` — form, columns, view components
   - `resources/js/types/` — TypeScript interfaces jika ada
3. Pastikan form field names match dengan backend validation rules

Output: Updated frontend files
```

---

## Fase 5: Factory & Seeder Updates

```
Update Laravel Factories dan Seeders agar sesuai dengan schema V2.

Referensi:
- Migration baru dan model yang sudah di-update
- `docs/database/00_products_design_v2.md`

Instruksi:
1. Update factories di `database/factories/`:
   - `ProductFactory.php` — `category_id` → `product_category_id`, hapus kolom yang di-drop
   - `BillOfMaterialFactory.php` — `quantity_required` → `quantity`, tambah `waste_percentage`
   - `ProductionOrderFactory.php` — rename columns
   - `ProductionOrderItemFactory.php` — `raw_material_id` → `product_id`
   - `ProductDependencyFactory.php` — rename columns
   - Factory lain yang terkait
2. Update seeders jika ada referensi ke kolom lama
3. Test: `./vendor/bin/sail artisan db:seed --class=ProductSeeder` (jika ada)

Output: Updated factory dan seeder files
```

---

## Fase 6: Test Updates

```
Update semua test files (Pest Feature, Unit, E2E) agar sesuai dengan perubahan schema V2.

Referensi:
- Perubahan di Fase 1-5
- `docs/module-registry.md` untuk standar testing

Instruksi:
1. Update Feature tests di `tests/Feature/Products/`:
   - Ganti semua referensi kolom lama ke baru
   - Pastikan assertions menggunakan nama kolom baru
2. Update Unit tests yang terkait products di `tests/Unit/`
3. Update E2E tests di `tests/e2e/products/`:
   - Form field selectors
   - Assertion text
4. Verifikasi:
   ```bash
   # Pest tests
   ./vendor/bin/sail test --group products

   # E2E tests
   npx playwright test tests/e2e/products/
   ```
5. Fix semua test yang gagal

Output: Updated test files, semua test PASS
```

---

## Fase 7: Finalisasi & Cleanup

```
Verifikasi akhir dan cleanup setelah migrasi V1 → V2 selesai.

Instruksi:
1. Jalankan full test suite:
   ```bash
   ./vendor/bin/sail test
   npx playwright test
   ```
2. Fix test yang gagal (jika ada yang terkait perubahan)
3. Update referensi desain:
   - Di `docs/prompt-recommendations.md` — ganti referensi v1 ke v2
   - Di `.agent/skills/` — update referensi jika ada
4. (Opsional) Hapus atau arsipkan file desain v1:
   - `docs/database/00_products_design.md` → archive atau hapus
5. Update `docs/database/migration_plan_v1_to_v2.md` — tandai semua fase sebagai selesai

Output: Semua test PASS, referensi updated
```

---

## Catatan untuk AI Agent

### Urutan Eksekusi
```
Fase 1 (Migration) → Fase 2 (Model) → Fase 3 (Backend) → Fase 4 (Frontend)
→ Fase 5 (Factory) → Fase 6 (Tests) → Fase 7 (Finalisasi)
```

### Sebelum Mulai Setiap Fase
1. Baca `docs/database/migration_plan_v1_to_v2.md` untuk konteks
2. Baca desain V2 yang relevan (`00_products_design_v2.md`)
3. Review hasil fase sebelumnya

### Skill yang Digunakan
- Fase 1: `database-migration` skill
- Fase 2-4: `refactor-backend` + `refactor-frontend` skills
- Fase 5-6: `testing-strategy` skill
