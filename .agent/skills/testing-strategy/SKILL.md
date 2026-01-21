---
name: Testing Strategy
description: Panduan membuat unit test, feature test, dan e2e test sesuai pola codebase.
---

# Testing Strategy Skill

Gunakan skill ini untuk membuat test yang konsisten dengan pola testing yang sudah ada di codebase.

## 🎯 Tipe Testing

```
Testing Pyramid:
    
       /\
      /  \     E2E Tests (Playwright)
     /----\    - User journey
    /      \   - Browser interaction
   /--------\  
  /          \ Feature Tests (PHPUnit)
 /            \- API endpoints
/              \- HTTP responses
/--------------\
|              | Unit Tests (PHPUnit)
|              | - Actions, Services
|              | - Requests, Resources
\--------------/
```

---

## 🚀 Quick Start

### Template Files
- [FeatureTest.php.template](file:///home/ariefn/project/erp/.agent/skills/testing-strategy/resources/FeatureTest.php.template)
- [UnitTest.php.template](file:///home/ariefn/project/erp/.agent/skills/testing-strategy/resources/UnitTest.php.template)
- [e2e.spec.ts.template](file:///home/ariefn/project/erp/.agent/skills/testing-strategy/resources/e2e.spec.ts.template)

### Commands
```bash
# Run all tests
./vendor/bin/sail test

# Run specific test
./vendor/bin/sail test --filter=EmployeeControllerTest

# Run e2e tests
./vendor/bin/sail npm run test:e2e

# Run specific e2e
./vendor/bin/sail npm run test:e2e -- --grep=employee
```

---

## 📁 Struktur Test Files

| Tipe | Lokasi | Contoh |
|------|--------|--------|
| Feature Test | `tests/Feature/` | `EmployeeControllerTest.php` |
| Unit Test (Actions) | `tests/Unit/Actions/{Module}/` | `IndexEmployeesActionTest.php` |
| Unit Test (Requests) | `tests/Unit/Requests/{Module}/` | `StoreEmployeeRequestTest.php` |
| Unit Test (Resources) | `tests/Unit/Resources/{Module}/` | `EmployeeResourceTest.php` |
| E2E Test | `tests/e2e/{module}/` | `add-employee.spec.ts` |

---

## 🧪 Feature Test (Controller)

### Yang Harus Ditest

| Method | Test Cases |
|--------|------------|
| `index` | List, pagination, search, filter, sort |
| `store` | Success, validation error, unauthorized |
| `show` | Success, not found |
| `update` | Success, validation error, unauthorized |
| `destroy` | Success, not found, unauthorized |
| `export` | Success, dengan filter |

### Contoh Pattern

```php
public function test_index_returns_paginated_employees(): void
{
    Employee::factory()->count(20)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/employees?per_page=10');

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'email']],
            'meta' => ['current_page', 'total'],
        ]);
}
```

---

## 🔬 Unit Test

### Actions
Test business logic dan orchestration:

```php
public function test_execute_returns_filtered_employees(): void
{
    $action = new IndexEmployeesAction(new EmployeeFilterService());
    $request = new IndexEmployeeRequest(['search' => 'John']);
    
    $result = $action->execute($request);
    
    $this->assertInstanceOf(LengthAwarePaginator::class, $result);
}
```

### Requests (Validation)
Test validation rules:

```php
public function test_name_is_required(): void
{
    $request = new StoreEmployeeRequest();
    $validator = Validator::make([], $request->rules());
    
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
}
```

### Resources
Test response structure:

```php
public function test_resource_contains_expected_fields(): void
{
    $employee = Employee::factory()->create();
    $resource = new EmployeeResource($employee);
    
    $this->assertArrayHasKey('id', $resource->toArray(request()));
    $this->assertArrayHasKey('name', $resource->toArray(request()));
}
```

---

## 🌐 E2E Test (Playwright)

### Yang Harus Ditest

| Flow | Test Cases |
|------|------------|
| List | Load page, render table |
| Create | Open modal, fill form, submit, see new item |
| Edit | Click edit, modify, save, see changes |
| Delete | Click delete, confirm, item gone |
| Filter | Apply filter, verify results |
| Export | Click export, verify download |

### Contoh Pattern

```typescript
test.describe('Employee CRUD', () => {
    test('can add new employee', async ({ page }) => {
        await page.goto('/employees');
        
        await page.click('[data-testid="add-employee-btn"]');
        await page.fill('[data-testid="employee-name-input"]', 'John Doe');
        await page.fill('[data-testid="employee-email-input"]', 'john@example.com');
        await page.click('[data-testid="employee-submit-btn"]');
        
        await expect(page.locator('text=John Doe')).toBeVisible();
    });
});
```

---

## ✅ Checklist Testing Lengkap

### Feature Test
- [ ] Test semua CRUD operations (index, store, show, update, destroy)
- [ ] Test validation errors (422)
- [ ] Test authorization/permission (403)
- [ ] Test not found (404)
- [ ] Test pagination, search, filter

### Unit Test
- [ ] Test Actions (business logic)
- [ ] Test Requests (validation rules)
- [ ] Test Resources (response structure)

### E2E Test
- [ ] Test user journey utama
- [ ] Gunakan `data-testid` untuk selectors
- [ ] Test responsive jika perlu

---

## 🛠️ Verification Commands

```bash
// turbo-all

# Backend tests
./vendor/bin/sail test

# Specific module
./vendor/bin/sail test --filter=Employee

# E2E tests
./vendor/bin/sail npm run test:e2e
```
