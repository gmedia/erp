---
name: Testing Strategy
description: Panduan membuat unit test, feature test, dan e2e test sesuai pola codebase.
---

# Testing Strategy Skill

Gunakan skill ini untuk membuat test yang konsisten dengan pola testing di codebase.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_last-error` | Debug test failures |
| `mcp_laravel-boost_tinker` | Inspect test data atau debug code snippets |
| `mcp_laravel-boost_browser-logs` | Debug E2E frontend errors |
| `read_file` | Baca file test referensi |

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
read_file(filePath: "/absolute/path/to/project/tests/Feature/EmployeeControllerTest.php", startLine: 1, endLine: 260)
read_file(filePath: "/absolute/path/to/project/tests/e2e/employees/add-employee.spec.ts", startLine: 1, endLine: 260)

# Debug test failure:
mcp_laravel-boost_last-error()
mcp_laravel-boost_browser-logs(entries: 10)  # untuk failure browser/E2E
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

---

## ✅ Verification

```bash
// turbo-all

# Lint / formatter guard
./vendor/bin/sail bin duster fix

# Backend tests
./vendor/bin/sail test

# Specific module
./vendor/bin/sail test --filter=Employee

# E2E tests  
./vendor/bin/sail npm run test:e2e
```

Jika test gagal:
```
mcp_laravel-boost_last-error()
mcp_laravel-boost_browser-logs(entries: 20)  # untuk failure browser/E2E
```
