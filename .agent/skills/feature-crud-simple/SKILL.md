---
name: Feature CRUD Simple
description: Workflow untuk membuat fitur CRUD sederhana (Departments, Positions, Branches, CustomerCategories, SupplierCategories) dengan satu tabel utama tanpa relasi kompleks.
---

# Feature CRUD Simple

Gunakan skill ini untuk membuat fitur CRUD sederhana yang hanya melibatkan satu tabel utama tanpa relasi foreign key.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Sebelum buat migration, lihat existing tables |
| `mcp_laravel-boost_list-routes` | Verifikasi routes setelah create |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Laravel jika ragu |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI (table, form, button) |
| `mcp_filesystem_read_file` | Baca file referensi existing |

---

## 🎯 Decision Tree: Simple vs Complex?

```
Apakah modul ini:
├── Punya relasi foreign key? → Complex CRUD
├── Butuh filter multi-field (range, date, dropdown)? → Complex CRUD
├── Perlu komponen React terpisah (Form, Filters)? → Complex CRUD
└── Hanya 1 tabel, filter search saja? → ✅ Simple CRUD
```

**Contoh Simple**: `positions`, `departments`, `branches`, `customer_categories`, `supplier_categories`
**Contoh Complex**: `employees` (relasi + filter kompleks)

---

## 📁 Struktur File

### Backend
| Layer | Path | Base Class |
|-------|------|------------|
| Model | `app/Models/{Feature}.php` | - |
| Controller | `app/Http/Controllers/{Feature}Controller.php` | - |
| Requests | `app/Http/Requests/{Features}/` | `SimpleCrudIndexRequest`, `SimpleCrudStoreRequest`, `SimpleCrudUpdateRequest`, `SimpleCrudExportRequest` |
| Resources | `app/Http/Resources/{Features}/` | `SimpleCrudResource`, `SimpleCrudCollection` |
| Actions | `app/Actions/{Features}/` | `SimpleCrudIndexAction`, `SimpleCrudExportAction` |
| FilterService | `app/Domain/{Features}/{Feature}FilterService.php` | Uses `BaseFilterService` trait |
| Exports | `app/Exports/{Feature}Export.php` | `SimpleCrudExport` |
| Routes | `routes/{feature}.php` | - |

### Frontend
| Path |
|------|
| `resources/js/pages/{features}/index.tsx` |
| `resources/js/utils/entityConfigs.ts` (tambah config baru) |

### Tests
| Path | Base Trait |
|------|------------|
| `tests/Unit/Models/{Feature}Test.php` | Pest syntax |
| `tests/Feature/{Features}/{Feature}ControllerTest.php` | `SimpleCrudTestTrait` |
| `tests/Feature/{Features}/{Feature}ExportTest.php` | `SimpleCrudExportTestTrait` |
| `tests/Unit/Actions/{Features}/Index{Features}ActionTest.php` | `SimpleCrudIndexActionTestTrait` |
| `tests/Unit/Actions/{Features}/Export{Features}ActionTest.php` | `SimpleCrudExportActionTestTrait` |
| `tests/Unit/Domain/{Features}/{Feature}FilterServiceTest.php` | `SimpleCrudFilterServiceTestTrait` |
| `tests/Unit/Requests/{Features}/Index{Feature}RequestTest.php` | `SimpleCrudIndexRequestTestTrait` |
| `tests/Unit/Requests/{Features}/Export{Feature}RequestTest.php` | `SimpleCrudExportRequestTestTrait` |
| `tests/Unit/Requests/{Features}/Store{Feature}RequestTest.php` | `SimpleCrudStoreRequestTestTrait` |
| `tests/Unit/Requests/{Features}/Update{Feature}RequestTest.php` | `SimpleCrudUpdateRequestTestTrait` |
| `tests/Unit/Resources/{Features}/{Feature}ResourceTest.php` | `SimpleCrudResourceTestTrait` |
| `tests/Unit/Resources/{Features}/{Feature}CollectionTest.php` | `SimpleCrudCollectionTestTrait` |
| `tests/e2e/{features}/helpers.ts` | Module-specific helper functions |
| `tests/e2e/{features}/{feature}.spec.ts` | Uses `generateModuleTests()` |

---

## 📖 Base Classes (WAJIB DIGUNAKAN!)

> [!IMPORTANT]
> Selalu extend base class yang sudah ada untuk mengurangi duplikasi kode!

### Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\{Feature}Factory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|{Feature} whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class {Feature} extends Model
{
    /** @use HasFactory<\Database\Factories\{Feature}Factory> */
    use HasFactory;

    protected $fillable = ['name'];
}
```

### Actions
```php
// app/Actions/{Features}/Index{Features}Action.php
use App\Actions\Concerns\SimpleCrudIndexAction;

class Index{Features}Action extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return {Feature}::class;
    }
}
```

```php
// app/Actions/{Features}/Export{Features}Action.php
use App\Actions\Concerns\SimpleCrudExportAction;

class Export{Features}Action extends SimpleCrudExportAction
{
    protected function getModelClass(): string { return {Feature}::class; }
    
    protected function getExportInstance(array $filters, ?Builder $query): FromQuery 
    { 
        return new {Feature}Export($filters, $query); 
    }
    
    protected function getFilenamePrefix(): string 
    { 
        return '{features}'; // snake_case untuk compound names (e.g., customer_categories)
    }
}
```

### FilterService
```php
// app/Domain/{Features}/{Feature}FilterService.php
use App\Domain\Concerns\BaseFilterService;

class {Feature}FilterService
{
    use BaseFilterService;
}
```

### Exports
```php
// app/Exports/{Feature}Export.php
use App\Exports\Concerns\SimpleCrudExport;

class {Feature}Export extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return {Feature}::class;
    }
}
```

### Requests
```php
// Semua request extend base class yang sesuai

// Index - No custom implementation
class Index{Feature}Request extends SimpleCrudIndexRequest {}

// Export - No custom implementation
class Export{Feature}Request extends SimpleCrudExportRequest {}

// Store - Requires getModelClass()
class Store{Feature}Request extends SimpleCrudStoreRequest 
{
    public function getModelClass(): string { return {Feature}::class; }
}

// Update - Requires getModelClass()
class Update{Feature}Request extends SimpleCrudUpdateRequest 
{
    public function getModelClass(): string { return {Feature}::class; }
}
```

### Resources
```php
// No custom implementation needed
class {Feature}Resource extends SimpleCrudResource {}
class {Feature}Collection extends SimpleCrudCollection {}
```

### Controller
```php
<?php

namespace App\Http\Controllers;

use App\Actions\{Features}\Export{Features}Action;
use App\Actions\{Features}\Index{Features}Action;
use App\Http\Requests\{Features}\Export{Feature}Request;
use App\Http\Requests\{Features}\Index{Feature}Request;
use App\Http\Requests\{Features}\Store{Feature}Request;
use App\Http\Requests\{Features}\Update{Feature}Request;
use App\Http\Resources\{Features}\{Feature}Collection;
use App\Http\Resources\{Features}\{Feature}Resource;
use App\Models\{Feature};
use Illuminate\Http\JsonResponse;

/**
 * Controller for {feature} management operations.
 *
 * Handles CRUD operations and export functionality for {features}.
 */
class {Feature}Controller extends Controller
{
    /**
     * Display a listing of the {features}.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(Index{Feature}Request $request): JsonResponse
    {
        ${features} = (new Index{Features}Action())->execute($request);

        return (new {Feature}Collection(${features}))->response();
    }

    /**
     * Store a newly created {feature} in storage.
     */
    public function store(Store{Feature}Request $request): JsonResponse
    {
        ${feature} = {Feature}::create($request->validated());

        return (new {Feature}Resource(${feature}))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified {feature}.
     */
    public function show({Feature} ${feature}): JsonResponse
    {
        return (new {Feature}Resource(${feature}))->response();
    }

    /**
     * Update the specified {feature} in storage.
     */
    public function update(Update{Feature}Request $request, {Feature} ${feature}): JsonResponse
    {
        ${feature}->update($request->validated());

        return (new {Feature}Resource(${feature}))->response();
    }

    /**
     * Remove the specified {feature} from storage.
     */
    public function destroy({Feature} ${feature}): JsonResponse
    {
        ${feature}->delete();

        return response()->json(null, 204);
    }

    /**
     * Export {features} to Excel based on filters.
     */
    public function export(Export{Feature}Request $request): JsonResponse
    {
        return (new Export{Features}Action())->execute($request);
    }
}
```

> **Important:** Actions diinstantiate langsung di controller, TIDAK menggunakan dependency injection.

### Routes
```php
<?php

use App\Http\Controllers\{Feature}Controller;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Frontend route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('{features}', function () {
        return Inertia::render('{features}/index');
    })->name('{features}')->middleware('permission:{feature}');
});

// API routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:{feature},true')->group(function () {
        Route::get('{features}', [{Feature}Controller::class, 'index']);
        Route::get('{features}/{{feature}}', [{Feature}Controller::class, 'show']);
        Route::post('{features}', [{Feature}Controller::class, 'store'])
            ->middleware('permission:{feature}.create,true');
        Route::put('{features}/{{feature}}', [{Feature}Controller::class, 'update'])
            ->middleware('permission:{feature}.edit,true');
        Route::delete('{features}/{{feature}}', [{Feature}Controller::class, 'destroy'])
            ->middleware('permission:{feature}.delete,true');
        Route::post('{features}/export', [{Feature}Controller::class, 'export']);
    });
});
```

> **Important:** Route parameter harus singular (e.g., `{position}`, `{customerCategory}`)

### Tests

#### Unit Model Test (Pest)
```php
<?php

use App\Models\{Feature};
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('{features}');

test('factory creates a valid {feature}', function () {
    ${feature} = {Feature}::factory()->create();

    assertDatabaseHas('{features}', ['id' => ${feature}->id]);

    expect(${feature}->getAttributes())->toMatchArray([
        'name' => ${feature}->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new {Feature})->getFillable();

    expect($fillable)->toBe(['name']);
});
```

#### Unit Action Tests
```php
// tests/Unit/Actions/{Features}/Index{Features}ActionTest.php
class Index{Features}ActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string { return Index{Features}Action::class; }
    protected function getModelClass(): string { return {Feature}::class; }
    protected function getRequestClass(): string { return Index{Feature}Request::class; }
}

// tests/Unit/Actions/{Features}/Export{Features}ActionTest.php
class Export{Features}ActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string { return Export{Features}Action::class; }
    protected function getModelClass(): string { return {Feature}::class; }
    protected function getRequestClass(): string { return Export{Feature}Request::class; }
    protected function getExpectedFilenamePrefix(): string { return '{features}'; }
}
```

#### Unit FilterService Test
```php
class {Feature}FilterServiceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string { return {Feature}FilterService::class; }
    protected function getModelClass(): string { return {Feature}::class; }
}
```

#### Unit Request Tests
```php
class Index{Feature}RequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;
    protected function getRequestClass(): string { return Index{Feature}Request::class; }
}

class Export{Feature}RequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;
    protected function getRequestClass(): string { return Export{Feature}Request::class; }
}

class Store{Feature}RequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudStoreRequestTestTrait;
    protected function getRequestClass(): string { return Store{Feature}Request::class; }
    protected function getModelClass(): string { return {Feature}::class; }
}

class Update{Feature}RequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudUpdateRequestTestTrait;
    protected function getRequestClass(): string { return Update{Feature}Request::class; }
    protected function getModelClass(): string { return {Feature}::class; }
    protected function getRouteParameterName(): string { return '{feature}'; }
}
```

#### Unit Resource Tests
```php
class {Feature}ResourceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudResourceTestTrait;
    protected function getResourceClass(): string { return {Feature}Resource::class; }
    protected function getModelClass(): string { return {Feature}::class; }
}

class {Feature}CollectionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudCollectionTestTrait;
    protected function getCollectionClass(): string { return {Feature}Collection::class; }
    protected function getModelClass(): string { return {Feature}::class; }
}
```

#### Feature Controller Test
```php
class {Feature}ControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;

    protected $modelClass = {Feature}::class;
    protected $endpoint = '/api/{features}';
    protected $permissionPrefix = '{feature}';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
```

#### Feature Export Test
```php
class {Feature}ExportTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportTestTrait;

    protected function getExportClass(): string { return {Feature}Export::class; }
    protected function getModelClass(): string { return {Feature}::class; }
    protected function getSampleData(): array
    {
        return [
            'match' => 'Engineering',  // Will be filtered
            'others' => ['Marketing', 'Sales'],  // Won't be filtered
        ];
    }
}
```

#### E2E Tests

> **PENTING:** Ikuti standar dari `tests/e2e/REFACTORING_PLAN.md`.
> Helper functions WAJIB di file terpisah (`tests/e2e/{features}/helpers.ts`), bukan di `helpers.ts` global.

```typescript
// tests/e2e/{features}/helpers.ts
import { Page, expect } from '@playwright/test';
import { login, createEntity, EntityConfig } from '../helpers';

export async function create{Feature}(
  page: Page,
  overrides: Partial<{ name: string }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/{features}',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

export async function search{Feature}(page: Page, name: string): Promise<void> {
  await page.fill('input[placeholder="Search {features}..."]', name);
  await page.press('input[placeholder="Search {features}..."]', 'Enter');
  await page.waitForSelector(`text=${name}`);
}
```

```typescript
// tests/e2e/{features}/{feature}.spec.ts
import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { create{Feature}, search{Feature} } from './helpers';

const config: ModuleTestConfig = {
  entityName: '{Feature}',
  entityNamePlural: '{Features}',
  route: '/{features}',
  apiPath: '/api/{features}',
  createEntity: create{Feature},
  searchEntity: search{Feature},
  sortableColumns: ['Name', 'Created At', 'Updated At'],
  viewType: 'dialog',
  viewDialogTitle: '{Feature} Details',
  exportApiPath: '/api/{features}/export',
  expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
};

generateModuleTests(config);
```

---

## 🎨 Frontend

### Page Component
```tsx
'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { {feature}Config } from '@/utils/entityConfigs';

export default createEntityCrudPage({feature}Config);
```

### Entity Configuration (tambah ke entityConfigs.ts)
```typescript
export const {feature}Config = createSimpleEntityConfig({
    entityName: '{Feature}',           // Singular, PascalCase (e.g., 'Customer Category')
    entityNamePlural: '{Features}',    // Plural, PascalCase (e.g., 'Customer Categories')
    apiBase: '{features}',             // Plural, kebab-case (e.g., 'customer-categories')
    filterPlaceholder: 'Search {features}...',  // Lowercase
});
```

---

## 📝 Naming Conventions

> [!IMPORTANT]
> Ikuti konvensi penamaan ini dengan ketat untuk konsistensi!

### Variabel & Class Naming
| Context | Pattern | Example (Single Word) | Example (Compound) |
|---------|---------|----------------------|-------------------|
| Model Class | PascalCase, Singular | `Position` | `CustomerCategory` |
| Controller Variable (Single) | camelCase, Singular | `$position` | `$customerCategory` |
| Controller Variable (Collection) | camelCase, Plural | `$positions` | `$customerCategories` |
| Action Class | PascalCase, Plural | `IndexPositionsAction` | `ExportCustomerCategoriesAction` |

### File & Directory Naming
| Type | Pattern | Example (Single Word) | Example (Compound) |
|------|---------|----------------------|-------------------|
| Route File | snake_case, singular | `position.php` | `customer_category.php` |
| Migration File | snake_case, plural | `create_positions_table` | `create_customer_categories_table` |
| Frontend Directory | kebab-case, plural | `positions/` | `customer-categories/` |
| E2E Test Directory | kebab-case, plural | `positions/` | `customer-categories/` |

### Route & API Naming
| Type | Pattern | Example (Single Word) | Example (Compound) |
|------|---------|----------------------|-------------------|
| Route Path | kebab-case, plural | `/positions` | `/customer-categories` |
| Route Parameter | camelCase, singular | `{position}` | `{customerCategory}` |
| Permission Prefix | snake_case, singular | `position` | `customer_category` |
| Export Filename Prefix | snake_case, plural | `positions` | `customer_categories` |

---

## 🚀 Langkah Implementasi

### Phase 1: Database & Model
```bash
// turbo-all
./vendor/bin/sail artisan make:migration create_{features}_table
./vendor/bin/sail artisan make:model {Feature} -f
./vendor/bin/sail artisan migrate
```

### Phase 2: Backend (urut dari dependency)
1. **Model** - Buat Model dengan `$fillable` dan PHPDoc
2. **FilterService** - Buat FilterService dengan BaseFilterService trait
3. **Requests** - Buat 4 request classes (Index, Export, Store, Update)
4. **Resources** - Buat Resource dan Collection
5. **Export** - Buat Export class
6. **Actions** - Buat Index dan Export actions
7. **Controller** - Buat Controller dengan 6 methods
8. **Routes** - Definisikan routes, include di `routes/web.php`

### Phase 3: Frontend
1. Tambah config di `resources/js/utils/entityConfigs.ts`
2. Buat halaman di `resources/js/pages/{features}/index.tsx`

### Phase 4: Testing (urut dari unit ke integration)
1. **Unit Tests:**
   - Model test (Pest)
   - Action tests
   - FilterService test
   - Request tests
   - Resource tests

2. **Feature Tests:**
   - Controller test
   - Export test

3. **E2E Tests:**
   - Buat helper functions di `tests/e2e/{features}/helpers.ts`
   - Buat spec file di `tests/e2e/{features}/{feature}.spec.ts` menggunakan `generateModuleTests()`

### Phase 5: Verification
```bash
// turbo-all
./vendor/bin/sail test --group {features}
npx playwright test tests/e2e/{features}/
```

---

## ✅ Checklist Sebelum PR

### Backend
- [ ] Model dengan `$fillable = ['name']`
- [ ] Model dengan PHPDoc annotations lengkap
- [ ] FilterService menggunakan `BaseFilterService` trait
- [ ] Semua Requests extend base class yang tepat
- [ ] Store & Update Requests implement `getModelClass()`
- [ ] Semua Resources extend base class tanpa custom implementation
- [ ] Actions extend base class yang tepat
- [ ] Export filename prefix menggunakan `snake_case`
- [ ] Export extend `SimpleCrudExport`
- [ ] Controller punya 6 methods standard
- [ ] Controller instantiate actions langsung (no DI)
- [ ] Routes file ikuti struktur standard
- [ ] Route parameter singular & camelCase

### Frontend
- [ ] Page component menggunakan `createEntityCrudPage()`
- [ ] Config menggunakan `createSimpleEntityConfig()`
- [ ] Config ditambahkan di `entityConfigs.ts`
- [ ] Entity name sesuai display format
- [ ] API base menggunakan kebab-case plural

### Testing
- [ ] Unit Model test di `tests/Unit/Models/` menggunakan Pest syntax
- [ ] Feature tests di `tests/Feature/{Features}/` subfolder
- [ ] Semua Action tests menggunakan traits
- [ ] FilterService test menggunakan trait
- [ ] Semua Request tests menggunakan traits
- [ ] Semua Resource tests menggunakan traits
- [ ] Feature Controller test menggunakan `SimpleCrudTestTrait`
- [ ] Feature Export test menggunakan `SimpleCrudExportTestTrait`
- [ ] Group annotation `->group('{features}')` di SEMUA test files
- [ ] E2E test menggunakan `generateModuleTests()` dari `shared-test-factories.ts`
- [ ] E2E helpers di `tests/e2e/{features}/helpers.ts` (bukan global)
- [ ] Semua tests pass: `sail test --group {features}` + `npx playwright test tests/e2e/{features}/`

### Naming Conventions
- [ ] Semua file mengikuti naming conventions
- [ ] Variable naming konsisten
- [ ] Route naming konsisten
- [ ] Permission naming konsisten

---

## 📋 Template Placeholders

Ketika membuat modul baru, replace placeholder berikut:

| Placeholder | Description | Example (Single) | Example (Compound) |
|-------------|-------------|------------------|-------------------|
| `{Feature}` | PascalCase, Singular | `Position` | `CustomerCategory` |
| `{Features}` | PascalCase, Plural | `Positions` | `CustomerCategories` |
| `{feature}` | camelCase, Singular | `position` | `customerCategory` |
| `{features}` | camelCase/kebab-case, Plural | `positions` / `positions` | `customerCategories` / `customer-categories` |

> **Note:** Untuk route paths dan frontend, gunakan kebab-case. Untuk variable PHP, gunakan camelCase.
