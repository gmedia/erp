---
name: testing-strategy
description: Panduan membuat unit test, feature test, dan e2e test sesuai pola codebase.
---

# Testing Strategy Skill

Gunakan skill ini untuk membuat test yang konsisten dengan pola testing di codebase.

## 🔧 Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `tail -n 50 storage/logs/laravel.log` | Aktifkan tool error/log saat test gagal |
| `./vendor/bin/sail artisan tinker --execute='...'` | Inspect test data atau debug code snippets |
| Playwright trace/screenshot (`npx playwright show-trace test-results/.../trace.zip`) | Aktifkan tool browser log untuk debug E2E frontend errors |
| Baca file | Baca file test referensi |

---

## 🎯 Tipe Testing

| Tipe | Lokasi | Fokus |
|------|--------|-------|
| Feature Test | `tests/Feature/` | API endpoints, HTTP responses |
| Unit Test | `tests/Unit/` | Actions, Requests, Resources |
| E2E Test | `tests/e2e/` | User journey, browser |

---

## 📁 Struktur Test Files

| Tipe | Lokasi | Contoh |
|------|--------|--------|
| Feature | `tests/Feature/` | `EmployeeControllerTest.php` |
| Unit Actions | `tests/Unit/Actions/{Module}/` | `IndexEmployeesActionTest.php` |
| Unit Requests | `tests/Unit/Requests/{Module}/` | `StoreEmployeeRequestTest.php` |
| E2E | `tests/e2e/{module}/` | `add-employee.spec.ts` |

---

## 📖 Referensi Pattern

```
# Baca file test existing untuk pattern:
baca tests/Feature/EmployeeControllerTest.php (baris 1–260)
baca tests/e2e/employees/add-employee.spec.ts (baris 1–260)

# Debug test failure:
tail -n 50 storage/logs/laravel.log                        # exception backend
npx playwright show-trace test-results/<test>/trace.zip   # E2E: browser/network log
```

| Pattern | File Referensi |
|---------|---------------|
| Feature Test | `tests/Feature/EmployeeControllerTest.php` |
| Action Unit Test | `tests/Unit/Actions/Employees/IndexEmployeesActionTest.php` |
| E2E Test | `tests/e2e/employees/add-employee.spec.ts` |

---

## 🧪 Feature Test (Controller)

> [!IMPORTANT]
> **Auth:** WAJIB gunakan `Sanctum::actingAs($user)` — bukan `actingAs($user)`  
> **Assertions:** Gunakan `assertJson()`, `assertJsonStructure()`, `assertOk()` — bukan `assertInertia()`  
> **Imports:** Import class yang dipakai (`Sanctum`, `Storage`, `Carbon`, `Rule`, model terkait) di header file, lalu gunakan short name. Hindari FQCN seperti `\Laravel\Sanctum\Sanctum`, `\Illuminate\Support\Facades\Storage`, atau `\Carbon\Carbon` di body test.  
> Ini karena arsitektur API-only (stateless Bearer Token), bukan session-based.

Test cases per method:

| Method | Test Cases |
|--------|------------|
| `index` | List, pagination, search, filter, sort |
| `store` | Success (201), validation error (422) |
| `update` | Success, validation error |
| `destroy` | Success (204), not found (404) |

---

## 🔬 Unit Test

### Actions
```php
$action = new IndexEmployeesAction(new EmployeeFilterService());
$result = $action->execute($request);
$this->assertInstanceOf(LengthAwarePaginator::class, $result);
```

### Requests (Validation)
```php
$request = new StoreEmployeeRequest();
$validator = Validator::make([], $request->rules());
$this->assertTrue($validator->fails());
```

---

## 🌐 E2E Test (Playwright)

> **PENTING**: Untuk refactoring E2E test existing, gunakan skill `refactor-e2e`.

| Flow | Test Cases |
|------|------------|
| Search | Search entity by identifier |
| Filters | Open filter dialog, apply filter |
| Add | Open modal, fill form, submit, see new item |
| View | Click actions → View, verify detail |
| Edit | Click actions → Edit, modify, save, verify |
| Export | Click export, download file, verify columns via ExcelJS |
| Checkbox | Body checkbox visible, header checkbox NOT visible |
| Sorting | Click all sortable column headers |
| Delete | Click actions → Delete, confirm, item gone |

**Selectors**: Gunakan `getByRole()`, `getByLabel()`, `getByPlaceholder()` — bukan `data-testid`

### Seed penuh untuk test baca-saja

Untuk test yang butuh seluruh data demo (mis. invariant integritas), gunakan trait `Tests\Concerns\RefreshDatabaseSeededOnce`:

```php
uses(RefreshDatabaseSeededOnce::class)->group('demo-integrity');
```

Trait ini men-seed `DatabaseSeeder` **sekali per proses** (bukan per test) di luar transaksi test, lalu membungkus tiap test dengan transaksi rollback. Jangan memakai `RefreshDatabase` + `$this->seed()` di `beforeEach` untuk suite baca-saja karena seed penuh akan diulang di setiap test.

---

## ✅ Verification

> **Pest:** satu proses saja (DB `testing` saling mengunci) dan selalu `timeout`; jalankan hanya grup/berkas terkait, bukan seluruh suite. **E2E:** lokal hanya spec yang diubah/ditambah (+ maks satu spec terdampak), sekali + `--last-failed`; pakai `bash scripts/e2e-full.sh <spec>` (bukan `sail npx playwright`). Suite penuh dijalankan CI.

```bash
// turbo-all

# Lint / formatter guard — hanya berkas PHP yang diubah
timeout 900 ./vendor/bin/sail bin duster fix $(git diff --name-only origin/main...HEAD -- '*.php')

# Backend tests — grup/berkas terkait saja, satu proses, ber-timeout
timeout 900 ./vendor/bin/sail test --group {slug} > /tmp/opencode/pest.log 2>&1

# E2E — hanya spec yang diubah/ditambah (bukan folder modul penuh)
bash scripts/e2e-full.sh tests/e2e/{module}/{spec}.spec.ts
```

Jika test gagal:
```
tail -n 50 storage/logs/laravel.log                        # exception backend
npx playwright show-trace test-results/<test>/trace.zip   # E2E: browser/network log
```
