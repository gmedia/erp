# Rekomendasi Prompt: Migrasi Database V1 → V2

> **✅ COMPLETED — 2026-05-02.** Semua 8 fase selesai. Dokumen ini diarsipkan sebagai referensi historis.
>
> **Cara pakai (archived):** Copy-paste prompt di bawah ini saat memulai sesi baru. Urutan eksekusi mengikuti nomor fase.

---

## Fase 1: Database Migration (Core — 7 tabel)

```
Buat migration baru untuk menyesuaikan schema Products CORE ke desain V2.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md` bagian "Detail Gap: Products Module (Core)"
- Desain target: `docs/database/00_products_design.md`
- Migration existing: `database/migrations/2026_01_29_114413_create_products_table.php` s/d `114419`

Instruksi:
1. Baca `docs/database/migration_plan_v1_to_v2.md` bagian "Detail Gap: Products Module (Core — 7 Tabel)"
2. Buat satu file migration untuk 7 tabel core:
   - `products` — rename category_id, drop 8 kolom ekstra, update FK constraint
   - `product_stocks` — alter integer → decimal, drop minimum_quantity
   - `bill_of_materials` — rename quantity_required → quantity, add waste_percentage
   - `production_orders` — rename 3 kolom, add 4 kolom baru. JANGAN rename total_cost (tetap di header)
   - `production_order_items` — rename raw_material_id → product_id, rename total_cost → cost, add 3 kolom
   - `product_dependencies` — rename 3 kolom, drop 2 kolom, recreate unique constraint
   - `product_prices` — alter nullable, alter NOT NULL, recreate unique constraint & FK
3. Perhatikan constraint/index changes yang didokumentasikan per tabel
4. Pastikan method `down()` bisa rollback semua perubahan
5. JANGAN jalankan migration dulu — tunggu review

Output: 1 file migration baru
```

---

## Fase 2: Database Migration (Subscription — 3 tabel)

```
Buat migration terpisah untuk menyederhanakan subscription tables ke V2.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md` bagian "Detail Gap: Subscription Module"
- Desain target: `docs/database/00_products_design.md` Section 3.D

Instruksi:
1. Karena subscription module BELUM punya frontend/controller/tests, gunakan strategi DROP & RECREATE:
   - Drop `subscription_billing_records` (child first)
   - Drop `customer_subscriptions` (child)
   - Drop `subscription_plans` (parent)
   - Recreate ketiga tabel sesuai V2 schema persis
2. Pastikan method `down()` bisa rollback (recreate V1 schema)
3. JANGAN jalankan migration dulu — tunggu review

Output: 1 file migration baru
```

---

## Fase 3: Model Updates

```
Update Eloquent Models agar sesuai dengan schema V2 setelah migration dijalankan.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md` bagian "Fase 3: Model Updates"
- Migration baru yang dibuat di Fase 1 & 2
- Desain V2: `docs/database/00_products_design.md`

Instruksi:
1. Baca migration baru untuk tahu perubahan schema yang pasti
2. Update model-model berikut sesuai perubahan kolom:
   - `app/Models/Product.php` — `category_id` → `product_category_id`, update $fillable, $casts, relations, hapus kolom yang di-drop dari $fillable
   - `app/Models/BillOfMaterial.php` — `quantity_required` → `quantity`, tambah `waste_percentage`
   - `app/Models/ProductionOrder.php` — rename columns, tambah relations baru (unit, createdBy). JANGAN rename total_cost.
   - `app/Models/ProductionOrderItem.php` — `raw_material_id` → `product_id`, `total_cost` → `cost`
   - `app/Models/ProductDependency.php` — `required_product_id` → `related_product_id`, `dependency_type` → `type`, `description` → `notes`
   - `app/Models/ProductStock.php` — update casts integer → decimal
   - `app/Models/ProductPrice.php` — nullable customer_category_id handling
   - `app/Models/SubscriptionPlan.php` — drop removed columns, `status` → `is_active` boolean
   - `app/Models/CustomerSubscription.php` — simplify to V2 columns
   - `app/Models/SubscriptionBillingRecord.php` — simplify, rename columns
3. Pastikan semua relationship methods tetap konsisten
4. Update PHPDoc annotations (@property) di setiap model

Output: Updated model files
```

---

## Fase 4: Backend (Controller, Request, Resource, Action)

```
Update backend layer (Controller, FormRequest, Resource, Action, DTO, Export) agar sesuai setelah model updates.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md` bagian "Blast Radius Summary"
- Model yang sudah di-update di Fase 3

Instruksi:
1. Cari semua referensi ke kolom lama menggunakan grep:
   - `category_id` (tanpa prefix `product_`) di konteks products
   - `quantity_required` di konteks BOM
   - `raw_material_id` di konteks production_order_items
   - `required_product_id` di konteks product_dependencies
   - `dependency_type` di konteks product_dependencies
   - `quantity_to_produce`, `production_date`, `completion_date` di konteks production_orders
   - `total_cost` di konteks production_order_items (BUKAN production_orders)
2. Update file-file berikut:
   - `app/Http/Controllers/ProductController.php`
   - `app/Http/Requests/Products/AbstractProductRequest.php`
   - `app/Http/Requests/Products/AbstractProductListingRequest.php`
   - `app/Http/Requests/Products/IndexProductRequest.php`
   - `app/Http/Resources/Products/ProductResource.php`
   - `app/Actions/Products/IndexProductsAction.php`
   - `app/Actions/Products/ExportProductsAction.php`
   - `app/Domain/Products/ProductFilterService.php`
   - `app/DTOs/Products/UpdateProductData.php`
   - `app/Exports/ProductExport.php`
3. Jangan update tests dulu — itu Fase 7

Output: Updated backend files
Verifikasi: Jalankan `./vendor/bin/sail test --group products` (expect beberapa test gagal karena factory belum di-update)
```

---

## Fase 5: Frontend Updates

```
Update frontend React SPA components agar sesuai dengan perubahan kolom backend.

Referensi:
- Gap analysis: `docs/database/migration_plan_v1_to_v2.md`
- Perubahan backend dari Fase 4

Instruksi:
1. Cari semua referensi ke kolom lama di `resources/js/`:
   - `category_id` → `product_category_id` (di konteks products, BUKAN customers/suppliers)
   - `quantity_required` → `quantity`
   - `dependency_type` → `type`
   - `raw_material_id` → `product_id`
   - `quantity_to_produce` → `quantity`
   - `production_date` → `planned_start_date`
   - `completion_date` → `planned_end_date`
2. Update file-file di:
   - `resources/js/components/products/ProductForm.tsx`
   - `resources/js/types/product.ts`
   - `resources/js/utils/schemas.ts` — Zod validation schemas
   - `resources/js/utils/entityConfigs.ts` — entity config references
3. Pastikan form field names match dengan backend validation rules
4. PERHATIAN: `category_id` juga dipakai di customers & suppliers — JANGAN ubah di konteks tersebut

Output: Updated frontend files
```

---

## Fase 6: Factory & Seeder Updates

```
Update Laravel Factories dan Seeders agar sesuai dengan schema V2.

Referensi:
- Migration baru dan model yang sudah di-update
- `docs/database/00_products_design.md`

Instruksi:
1. Update factories di `database/factories/`:
   - `ProductFactory.php` — `category_id` → `product_category_id`, hapus kolom yang di-drop
   - `BillOfMaterialFactory.php` — `quantity_required` → `quantity`, tambah `waste_percentage`
   - `ProductionOrderFactory.php` — rename columns, add unit_id & created_by
   - `ProductionOrderItemFactory.php` — `raw_material_id` → `product_id`, `total_cost` → `cost`
   - `ProductDependencyFactory.php` — `required_product_id` → `related_product_id`, `dependency_type` → `type`
   - `ProductPriceFactory.php` — nullable customer_category_id
   - `SubscriptionPlanFactory.php` — rewrite sesuai V2 schema
   - `CustomerSubscriptionFactory.php` — rewrite sesuai V2 schema
   - `SubscriptionBillingRecordFactory.php` — rewrite sesuai V2 schema
2. Update seeders:
   - `database/seeders/ProductSampleDataSeeder.php` — update semua referensi kolom lama
3. Verifikasi: `./vendor/bin/sail artisan db:seed --class=ProductSampleDataSeeder`

Output: Updated factory dan seeder files
```

---

## Fase 7: Test Updates

```
Update semua test files (Pest Feature, Unit, E2E) agar sesuai dengan perubahan schema V2.

Referensi:
- Perubahan di Fase 1-6
- Blast radius di `docs/database/migration_plan_v1_to_v2.md`

Instruksi:
1. Update Feature tests:
   - `tests/Feature/Products/ProductControllerTest.php`
   - `tests/Feature/Products/ProductExportTest.php`
2. Update Unit tests:
   - `tests/Unit/Actions/Products/IndexProductsActionTest.php`
   - `tests/Unit/Domain/Products/ProductFilterServiceTest.php`
   - `tests/Unit/Models/ProductTest.php`
   - `tests/Unit/Requests/Products/ExportProductRequestTest.php`
   - `tests/Unit/Requests/Products/IndexProductRequestTest.php`
   - `tests/Unit/Requests/Products/StoreProductRequestTest.php`
   - `tests/Unit/Resources/Products/ProductResourceTest.php`
3. Update E2E tests:
   - `tests/e2e/products/helpers.ts` — form field selectors, assertion text
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

## Fase 8: Finalisasi & Cleanup

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
   - Di `.github/skills/` — update referensi jika ada
   - Di `docs/database/IMPLEMENTATION_STATUS.md` — update status Products ke "✅ Implemented"
4. Hapus file desain v1:
   - `docs/database/00_products_design.md` → hapus (V2 adalah satu-satunya source of truth)
5. Update `docs/database/migration_plan_v1_to_v2.md` — tandai semua fase sebagai selesai
6. Jalankan `./vendor/bin/sail bin duster fix` untuk memastikan code style konsisten

Output: Semua test PASS, referensi updated, V1 dihapus
```

---

## Catatan untuk AI Agent

### Urutan Eksekusi
```
Fase 1 (Migration Core) → Fase 2 (Migration Subscription) → Fase 3 (Model)
→ Fase 4 (Backend) → Fase 5 (Frontend) → Fase 6 (Factory)
→ Fase 7 (Tests) → Fase 8 (Finalisasi)
```

### Sebelum Mulai Setiap Fase
1. Baca `docs/database/migration_plan_v1_to_v2.md` untuk konteks (terutama Blast Radius Summary)
2. Baca desain V2 yang relevan (`00_products_design.md`)
3. Review hasil fase sebelumnya

### Skill yang Digunakan
- Fase 1-2: `.github/skills/database-migration` skill
- Fase 3-4: `.github/skills/refactor-backend` skill
- Fase 5: `.github/skills/refactor-frontend` skill
- Fase 6-7: `.github/skills/testing-strategy` skill

### Peringatan Penting
- `total_cost` di `production_orders` (header) **TIDAK** di-rename. Hanya `total_cost` di `production_order_items` yang berubah ke `cost`.
- `category_id` di `customers` dan `suppliers` **TIDAK** diubah. Hanya `category_id` di `products` yang berubah ke `product_category_id`.
- Subscription tables (Fase 2) menggunakan strategi DROP & RECREATE karena belum ada implementasi frontend/controller.
