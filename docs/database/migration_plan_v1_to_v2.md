# Rencana Migrasi Database: V1 → V2

> **Konteks:** Dokumen ini berisi gap analysis antara schema database saat ini (v1) dan desain baru (v2), serta tahapan migrasi yang harus dilakukan. Gunakan dokumen ini sebagai referensi utama saat mengerjakan migrasi.

---

## Referensi Desain

| Modul | V1 (Saat Ini) | V2 (Target) |
| :--- | :--- | :--- |
| Products | `docs/database/00_products_design.md` | `docs/database/00_products_design_v2.md` |

---

## Status Gap per Modul

| Modul | Gap | Migration Baru? | Effort |
| :--- | :--- | :--- | :--- |
| **COA** | ✅ Identik | Tidak | — |
| **Asset Management** | ✅ Identik | Tidak | — |
| **Products (Core)** | ⚠️ Banyak perbedaan | Ya | Tinggi (~8 jam) |
| **Products (Subscription)** | ⚠️ Overbuilt, perlu simplifikasi | Ya (terpisah) | Rendah (~1 jam) |

---

## Pre-flight Checklist

Sebelum memulai migrasi, pastikan:

- [ ] Semua test PASS di state saat ini (`./vendor/bin/sail test`)
- [ ] Tidak ada PR terbuka yang menyentuh products module
- [ ] Database sudah di-backup (jika ada data penting)
- [ ] Keputusan pending sudah di-resolve (lihat Section "Keputusan yang Sudah Di-resolve")
- [ ] Branch khusus sudah dibuat (mis. `refactor/products-v2-migration`)

---

## Blast Radius Summary

Total file terdampak berdasarkan scan codebase:

| Layer | Jumlah File | Detail |
| :--- | :--- | :--- |
| Models | 6 | `Product`, `BillOfMaterial`, `ProductionOrder`, `ProductionOrderItem`, `ProductDependency`, `ProductPrice` |
| Controllers/Actions/Domain | 4 | `ProductController`, `IndexProductsAction`, `ExportProductsAction`, `ProductFilterService` |
| Requests | 3 | `AbstractProductRequest`, `AbstractProductListingRequest`, `IndexProductRequest` |
| Resources | 1 | `ProductResource` |
| DTOs | 1 | `UpdateProductData` |
| Exports | 1 | `ProductExport` |
| Factories | 6 | `ProductFactory`, `BillOfMaterialFactory`, `ProductionOrderFactory`, `ProductionOrderItemFactory`, `ProductDependencyFactory`, `ProductPriceFactory` |
| Seeders | 1 | `ProductSampleDataSeeder` |
| Tests (Feature) | 2 | `ProductControllerTest`, `ProductExportTest` |
| Tests (Unit) | 5 | Actions, Domain, Models, Requests, Resources |
| Tests (E2E) | 1 | `tests/e2e/products/helpers.ts` |
| Frontend (TS/TSX) | 4 | `ProductForm.tsx`, `product.ts`, `schemas.ts`, `entityConfigs.ts` |
| **Total** | **~35 files** | |

---

## Detail Gap: Products Module (Core — 7 Tabel)

### A. Tabel `products`

**Kolom yang perlu di-rename:**

| V1 (Saat Ini) | V2 (Target) | Dampak |
| :--- | :--- | :--- |
| `category_id` | `product_category_id` | ~25 files (model, controller, request, resource, factory, tests, frontend) |

**Kolom ekstra di V1 (tidak ada di V2) — KEPUTUSAN: DROP ALL:**

| Kolom | Tipe | Alasan Drop |
| :--- | :--- | :--- |
| `markup_percentage` | Decimal(5,2) nullable | Tidak digunakan di UI/logic manapun |
| `is_recurring` | Boolean | Redundan dengan `billing_model` |
| `trial_period_days` | Integer nullable | Pindah ke `subscription_plans.trial_period_days` |
| `allow_one_time_purchase` | Boolean | Redundan dengan `billing_model` |
| `is_manufactured` | Boolean | Teridentifikasi dari `type = finished_good` + ada BOM |
| `is_purchasable` | Boolean | Tidak digunakan di logic manapun |
| `is_sellable` | Boolean | Tidak digunakan di logic manapun |
| `is_taxable` | Boolean | Tidak digunakan di logic manapun |

**Index yang perlu diupdate:**

| Aksi | Detail |
| :--- | :--- |
| Drop index | `products_is_manufactured_index` (kolom di-drop) |
| Rename FK constraint | `products_category_id_foreign` → `products_product_category_id_foreign` |

### B. Tabel `product_stocks`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| `quantity_on_hand` type | Integer | Decimal(15,2) |
| `quantity_reserved` type | Integer | Decimal(15,2) |
| `minimum_quantity` | Ada | **Drop** |

### C. Tabel `bill_of_materials`

| Perubahan | V1 | V2 | Catatan |
| :--- | :--- | :--- | :--- |
| Column rename | `quantity_required` | `quantity` | Tipe tetap Decimal(15,4) |
| Column baru | — | `waste_percentage` Decimal(5,2) default 0 | |

### D. Tabel `production_orders`

| Perubahan | V1 | V2 | Catatan |
| :--- | :--- | :--- | :--- |
| Rename | `quantity_to_produce` | `quantity` | |
| Rename | `production_date` | `planned_start_date` | |
| Rename | `completion_date` | `planned_end_date` | |
| Tambah | — | `actual_start_date` Date nullable | |
| Tambah | — | `actual_end_date` Date nullable | |
| Tambah | — | `unit_id` FK → `units` | |
| Tambah | — | `created_by` FK → `users` nullable | |
| **TETAP** | `total_cost` | `total_cost` | **TIDAK di-rename** — tetap `total_cost` di header |

### E. Tabel `production_order_items`

| Perubahan | V1 | V2 | Catatan |
| :--- | :--- | :--- | :--- |
| Rename FK | `raw_material_id` | `product_id` | Lebih fleksibel (bisa WIP/purchased_good) |
| Rename | `total_cost` | `cost` | **HANYA di items**, bukan di header `production_orders` |
| Tambah | — | `quantity_planned` Decimal(15,2) | Dari BOM |
| Tambah | — | `unit_id` FK → `units` | |
| Tambah | — | `notes` Text nullable | |

**Constraint yang perlu diupdate:**

| Aksi | Detail |
| :--- | :--- |
| Rename FK | `production_order_items_raw_material_id_foreign` → `production_order_items_product_id_foreign` |
| Rename index | `production_order_items_raw_material_id_index` → `production_order_items_product_id_index` |

### F. Tabel `product_dependencies`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Rename FK | `required_product_id` | `related_product_id` |
| Rename | `dependency_type` | `type` |
| Rename | `description` | `notes` |
| Drop | `minimum_quantity` | — |
| Drop | `is_active` | — |

**Constraint yang perlu diupdate:**

| Aksi | Detail |
| :--- | :--- |
| Drop unique | `product_deps_unique` (`product_id`, `required_product_id`, `dependency_type`) |
| Create unique | `product_dependencies_product_id_related_product_id_type_unique` (`product_id`, `related_product_id`, `type`) |
| Rename FK | `product_dependencies_required_product_id_foreign` → `product_dependencies_related_product_id_foreign` |
| Drop index | `product_dependencies_dependency_type_index` (kolom di-rename) |
| Add index | `product_dependencies_type_index` |

### G. Tabel `product_prices`

| Perubahan | V1 | V2 | Catatan |
| :--- | :--- | :--- | :--- |
| `customer_category_id` | NOT NULL | **Nullable** | NULL = harga default semua kategori |
| `effective_from` | Nullable | **NOT NULL** | Harus selalu punya tanggal mulai |

**Constraint yang perlu diupdate:**

| Aksi | Detail |
| :--- | :--- |
| Drop unique | `product_prices_unique` (`product_id`, `customer_category_id`, `effective_from`) |
| Create unique | Baru: `product_prices_product_id_customer_category_id_effective_from_unique` — **perhatian**: unique constraint dengan nullable column di PostgreSQL mengizinkan multiple NULL. Validasi duplikasi dilakukan di application level. |
| Drop FK constraint | `product_prices_customer_category_id_foreign` (karena nullable, perlu recreate) |
| Create FK | `customer_category_id` nullable, constrained to `customer_categories`, onDelete `set null` |

---

## Detail Gap: Subscription Module (3 Tabel — Fase Terpisah)

> **Strategi:** Subscription module belum punya implementasi frontend/controller/tests. Daripada migrate kolom satu-satu, lebih efisien untuk **drop & recreate** ketiga tabel sesuai V2 schema. Ini aman karena tidak ada kode yang bergantung pada tabel ini selain Model dan Factory.

### H. Tabel `subscription_plans`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Drop | `code`, `description`, `billing_interval_count`, `minimum_commitment_cycles`, `auto_renew` | — |
| Alter | `status` enum (`active`, `inactive`, `archived`) → `is_active` boolean | — |
| Drop enum value | `biennial` dari `billing_interval` | — |

### I. Tabel `customer_subscriptions`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| V1 overbuilt | 15+ kolom | 9 kolom |
| Drop | `subscription_number`, `product_id`, `trial_start_date`, `trial_end_date`, `current_period_start`, `current_period_end`, `cancellation_date`, `cancellation_effective_date`, `billing_cycles_completed`, `auto_renew`, `recurring_amount` | — |
| Tambah | — | `next_billing_date` Date |
| Alter enum | Remove `suspended` | — |
| Drop | `softDeletes` | — |

### J. Tabel `subscription_billing_records`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| V1 overbuilt | 15+ kolom | 10 kolom |
| Drop | `invoice_number`, `billing_date`, `due_date`, `subtotal`, `amount_paid`, `paid_date`, `payment_method`, `payment_reference`, `retry_count`, `next_retry_date` | — |
| Rename | `total_amount` → `total` | — |
| Rename | `period_start` → `billing_period_start` | — |
| Rename | `period_end` → `billing_period_end` | — |
| Alter enum | 7 values → 4 values (`pending`, `paid`, `overdue`, `cancelled`) | — |
| Tambah | — | `paid_at` Timestamp nullable (menggantikan `paid_date`) |

---

## Tahapan Migrasi

### Fase 1: Database Migration (Core — 7 tabel)

Buat migration baru yang berisi perubahan schema untuk tabel core products.

**Nama file:** `{timestamp}_align_products_core_to_v2.php`

**File terdampak:**
- `database/migrations/` (1 file baru)

**Estimasi:** 30 menit

### Fase 2: Database Migration (Subscription — 3 tabel)

Buat migration terpisah untuk subscription tables (drop & recreate).

**Nama file:** `{timestamp}_align_subscription_tables_to_v2.php`

**File terdampak:**
- `database/migrations/` (1 file baru)

**Estimasi:** 15 menit

### Fase 3: Model Updates

Update Eloquent Model agar reflect schema baru.

**File terdampak:**
- `app/Models/Product.php` — rename `category_id` → `product_category_id`, update fillable/casts, drop removed columns
- `app/Models/BillOfMaterial.php` — rename `quantity_required` → `quantity`, add `waste_percentage`
- `app/Models/ProductionOrder.php` — rename columns, add new relations (`unit`, `createdBy`)
- `app/Models/ProductionOrderItem.php` — rename `raw_material_id` → `product_id`, `total_cost` → `cost`
- `app/Models/ProductDependency.php` — rename `required_product_id` → `related_product_id`, `dependency_type` → `type`
- `app/Models/ProductStock.php` — update casts integer → decimal
- `app/Models/ProductPrice.php` — nullable `customer_category_id` handling
- `app/Models/SubscriptionPlan.php` — `status` → `is_active`, drop removed columns
- `app/Models/CustomerSubscription.php` — simplify to 9 columns
- `app/Models/SubscriptionBillingRecord.php` — simplify, rename columns

**Estimasi:** 1 jam

### Fase 4: Backend (Controller, Request, Resource, Action, DTO)

**File terdampak:**
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

**Estimasi:** 2 jam

### Fase 5: Frontend

**File terdampak:**
- `resources/js/components/products/ProductForm.tsx`
- `resources/js/types/product.ts`
- `resources/js/utils/schemas.ts`
- `resources/js/utils/entityConfigs.ts`

**Estimasi:** 1.5 jam

### Fase 6: Factory & Seeder

**File terdampak:**
- `database/factories/ProductFactory.php`
- `database/factories/BillOfMaterialFactory.php`
- `database/factories/ProductionOrderFactory.php`
- `database/factories/ProductionOrderItemFactory.php`
- `database/factories/ProductDependencyFactory.php`
- `database/factories/ProductPriceFactory.php`
- `database/factories/SubscriptionPlanFactory.php`
- `database/factories/CustomerSubscriptionFactory.php`
- `database/factories/SubscriptionBillingRecordFactory.php`
- `database/seeders/ProductSampleDataSeeder.php`

**Estimasi:** 30 menit

### Fase 7: Tests

**File terdampak:**
- `tests/Feature/Products/ProductControllerTest.php`
- `tests/Feature/Products/ProductExportTest.php`
- `tests/Unit/Actions/Products/IndexProductsActionTest.php`
- `tests/Unit/Domain/Products/ProductFilterServiceTest.php`
- `tests/Unit/Models/ProductTest.php`
- `tests/Unit/Requests/Products/ExportProductRequestTest.php`
- `tests/Unit/Requests/Products/IndexProductRequestTest.php`
- `tests/Unit/Requests/Products/StoreProductRequestTest.php`
- `tests/Unit/Resources/Products/ProductResourceTest.php`
- `tests/e2e/products/helpers.ts`

**Estimasi:** 2-3 jam

### Fase 8: Verifikasi & Cleanup

```bash
./vendor/bin/sail test
npx playwright test tests/e2e/products/
```

**Estimasi:** 30 menit

---

## Estimasi Total

| Fase | Estimasi | Status |
| :--- | :--- | :--- |
| 1. Migration (Core) | 30 menit | ✅ Done |
| 2. Migration (Subscription) | 15 menit | ✅ Done |
| 3. Models | 1 jam | ✅ Done |
| 4. Backend | 2 jam | ✅ Done |
| 5. Frontend | 1.5 jam | ✅ Done |
| 6. Factory & Seeder | 30 menit | ✅ Done |
| 7. Tests | 2-3 jam | ✅ Done |
| 8. Verifikasi | 30 menit | ✅ Done |
| **Total** | **~8-9 jam** | **✅ Complete** |

---

## Rollback Strategy

Migrasi ini bersifat **all-or-nothing per fase**:

1. **Jika migration gagal (Fase 1-2):** Rollback via `php artisan migrate:rollback`. Method `down()` harus bisa reverse semua perubahan.
2. **Jika kode gagal (Fase 3-7):** Karena dikerjakan di branch terpisah, cukup `git checkout main` untuk rollback.
3. **Point of no return:** Setelah migration dijalankan di production dan data baru sudah masuk dengan schema V2, rollback tidak lagi trivial.

**Rekomendasi:** Kerjakan seluruh fase di satu branch, merge sekaligus setelah semua test PASS.

---

## Keputusan yang Sudah Di-resolve

| # | Keputusan | Resolusi | Alasan |
| :--- | :--- | :--- | :--- |
| 1 | Drop vs keep kolom ekstra V1 | **Drop semua** | Tidak ada kode yang menggunakan kolom-kolom ini |
| 2 | Scope Subscription | **Fase terpisah (drop & recreate)** | Tidak ada frontend/controller/tests — lebih efisien recreate |
| 3 | `total_cost` rename scope | **Hanya di `production_order_items`** | `production_orders.total_cost` tetap (header cache field) |
| 4 | `product_prices` unique constraint + nullable | **Application-level validation** | PostgreSQL unique constraint dengan NULL column mengizinkan multiple NULL rows |

---

## Catatan Penting

### COA & Asset Management
Schema sudah identik antara v1 dan v2. Yang perlu dilakukan:
1. Ganti referensi desain dari v1 → v2 di `.github/skills/` dan `docs/prompt-recommendations.md`
2. Verifikasi kode existing sesuai panduan v2
3. Fitur baru dari v2 (Pipeline integration, dll.) bisa dijadikan fase tersendiri

### Setelah Migrasi Selesai
1. Update `docs/database/IMPLEMENTATION_STATUS.md` — status Products → "✅ Implemented"
2. Hapus atau arsipkan `docs/database/00_products_design.md` (V1)
3. Update `docs/prompt-recommendations.md` — referensi ke V2
