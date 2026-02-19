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
| **Products** | ⚠️ Banyak perbedaan | Ya | Tinggi |

---

## Detail Gap: Products Module

### A. Tabel `products`

**Kolom yang perlu di-rename:**

| V1 (Saat Ini) | V2 (Target) | Dampak |
| :--- | :--- | :--- |
| `category_id` | `product_category_id` | Model, Controller, Form, Factory, Seeder, Tests |

**Kolom ekstra di V1 (tidak ada di V2):**

| Kolom | Tipe | Keputusan |
| :--- | :--- | :--- |
| `markup_percentage` | Decimal(5,2) nullable | **Drop** atau pertahankan |
| `is_recurring` | Boolean | **Drop** (redundan dengan `billing_model`) |
| `trial_period_days` | Integer nullable | **Drop** (pindah ke `subscription_plans`) |
| `allow_one_time_purchase` | Boolean | **Drop** (redundan dengan `billing_model`) |
| `is_manufactured` | Boolean | **Drop** (teridentifikasi dari `type`) |
| `is_purchasable` | Boolean | **Drop** atau pertahankan |
| `is_sellable` | Boolean | **Drop** atau pertahankan |
| `is_taxable` | Boolean | **Drop** atau pertahankan |

### B. Tabel `product_stocks`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| `quantity_on_hand` type | Integer | Decimal(15,2) |
| `quantity_reserved` type | Integer | Decimal(15,2) |
| `minimum_quantity` | Ada | **Drop** |

### C. Tabel `bill_of_materials`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Column rename | `quantity_required` | `quantity` |
| Column baru | — | `waste_percentage` Decimal(5,2) default 0 |

### D. Tabel `production_orders`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Rename | `quantity_to_produce` | `quantity` |
| Rename | `production_date` | `planned_start_date` |
| Rename | `completion_date` | `planned_end_date` |
| Tambah | — | `actual_start_date` Date nullable |
| Tambah | — | `actual_end_date` Date nullable |
| Tambah | — | `unit_id` FK → `units` |
| Tambah | — | `created_by` FK → `users` nullable |

### E. Tabel `production_order_items`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Rename FK | `raw_material_id` | `product_id` |
| Rename | `total_cost` | `cost` |
| Tambah | — | `quantity_planned` Decimal(15,2) |
| Tambah | — | `unit_id` FK → `units` |
| Tambah | — | `notes` Text nullable |

### F. Tabel `product_dependencies`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Rename FK | `required_product_id` | `related_product_id` |
| Rename | `dependency_type` | `type` |
| Rename | `description` | `notes` |
| Drop | `minimum_quantity` | — |
| Drop | `is_active` | — |

### G. Tabel `product_prices`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| `customer_category_id` | NOT NULL | **Nullable** (NULL = harga default) |
| `effective_from` | Nullable | **NOT NULL** |

### H. Tabel `subscription_plans`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| Drop | `code`, `description`, `billing_interval_count`, `minimum_commitment_cycles`, `auto_renew` | — |
| Alter | `status` enum → `is_active` boolean | — |

### I. Tabel `customer_subscriptions`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| V1 overbuilt | 15+ kolom | 9 kolom |
| Drop | `subscription_number`, `product_id`, trial dates, cancellation dates, `billing_cycles_completed`, `auto_renew`, `recurring_amount` | — |
| Tambah | — | `next_billing_date` Date |
| Alter enum | Includes `suspended` | Remove `suspended` |

### J. Tabel `subscription_billing_records`

| Perubahan | V1 | V2 |
| :--- | :--- | :--- |
| V1 overbuilt | 15+ kolom | 10 kolom |
| Drop | `invoice_number`, `billing_date`, `due_date`, `subtotal`, `amount_paid`, `paid_date`, `payment_method`, `payment_reference`, `retry_count`, `next_retry_date` | — |
| Rename | `total_amount` → `total` | — |
| Alter enum | 7 values | 4 values |

---

## Tahapan Migrasi

### Fase 1: Database Migration (Products)
Buat migration baru `2026_02_19_000001_align_products_to_v2.php` yang berisi semua perubahan schema di atas.

**File terdampak:**
- `database/migrations/` (1 file baru)

### Fase 2: Model Updates
Update Eloquent Model agar reflect schema baru.

**File terdampak:**
- `app/Models/Product.php` — rename `category_id` → `product_category_id`, update fillable/casts
- `app/Models/BillOfMaterial.php` — rename column references
- `app/Models/ProductionOrder.php` — rename columns, add relations
- `app/Models/ProductionOrderItem.php` — rename FK
- `app/Models/ProductDependency.php` — rename columns
- `app/Models/ProductStock.php` — update casts
- `app/Models/ProductPrice.php` — nullable handling
- (Opsional) Subscription models

### Fase 3: Backend (Controller, Request, Resource, Action)
**File terdampak:**
- `app/Http/Controllers/ProductController.php`
- `app/Http/Requests/` — product-related requests
- `app/Http/Resources/` — product-related resources
- `app/Actions/` — product-related actions

### Fase 4: Frontend
**File terdampak:**
- `resources/js/pages/products/` — form, columns, views

### Fase 5: Factory, Seeder
**File terdampak:**
- `database/factories/` — product-related factories
- `database/seeders/` — product-related seeders

### Fase 6: Tests
**File terdampak:**
- `tests/Feature/Products/`
- `tests/Unit/` (Models, Actions, Requests, Resources, Domain terkait products)
- `tests/e2e/products/`

### Fase 7: Verifikasi
```bash
# Pest tests
./vendor/bin/sail test --group products

# E2E tests
npx playwright test tests/e2e/products/

# Full suite
./vendor/bin/sail test
npx playwright test
```

---

## Catatan Penting

### COA & Asset Management
Schema sudah identik antara v1 dan v2. Yang perlu dilakukan:
1. Ganti referensi desain dari v1 → v2 di agent skills/prompts
2. Verifikasi kode existing sesuai panduan v2
3. Fitur baru dari v2 (Pipeline integration, dll.) bisa dijadikan fase tersendiri

### Keputusan Pending
- **Pendekatan migrasi**: Full migrate ke v2 (drop semua kolom ekstra) vs Adopt v2 + keep kolom v1 berguna
- **Scope Subscription**: Apakah subscription tables sudah ada implementasi frontend/controller?
