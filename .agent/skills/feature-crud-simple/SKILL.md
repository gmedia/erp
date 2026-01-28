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
| Exports | `app/Exports/` | `SimpleCrudExport` |
| Routes | `routes/{feature}.php` | - |

### Frontend
| Path |
|------|
| `resources/js/pages/{features}/index.tsx` |
| `resources/js/utils/entityConfigs.ts` (tambah config baru) |

### Tests
| Path |
|------|
| `tests/Feature/{Feature}ControllerTest.php` (use `SimpleCrudTestTrait`) |
| `tests/Feature/{Feature}ExportTest.php` |
| `tests/e2e/{features}/` |

---

## 📖 Base Classes (WAJIB DIGUNAKAN!)

> [!IMPORTANT]
> Selalu extend base class yang sudah ada untuk mengurangi duplikasi kode!

### Actions
```php
// app/Actions/{Features}/Index{Features}Action.php
use App\Actions\Concerns\SimpleCrudIndexAction;

class IndexFeaturesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Feature::class;
    }
}
```

```php
// app/Actions/{Features}/Export{Features}Action.php
use App\Actions\Concerns\SimpleCrudExportAction;

class ExportFeaturesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string { return Feature::class; }
    protected function getExportInstance(array $filters, ?Builder $query): FromQuery 
    { 
        return new FeatureExport($filters, $query); 
    }
    protected function getFilenamePrefix(): string { return 'features'; }
}
```

### Exports
```php
// app/Exports/{Feature}Export.php
use App\Exports\Concerns\SimpleCrudExport;

class FeatureExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Feature::class;
    }
}
```

### Requests
```php
// Semua request extend base class yang sesuai
class IndexFeatureRequest extends SimpleCrudIndexRequest {}
class ExportFeatureRequest extends SimpleCrudExportRequest {}
class StoreFeatureRequest extends SimpleCrudStoreRequest 
{
    public function getModelClass(): string { return Feature::class; }
}
class UpdateFeatureRequest extends SimpleCrudUpdateRequest 
{
    public function getModelClass(): string { return Feature::class; }
}
```

### Resources
```php
class FeatureResource extends SimpleCrudResource {}
class FeatureCollection extends SimpleCrudCollection {}
```

### Tests
```php
class FeatureControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;
    
    protected $modelClass = Feature::class;
    protected $endpoint = '/api/features';
    protected $permissionPrefix = 'feature';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
```

---

## 🚀 Langkah Implementasi

### Phase 1: Database & Model
```bash
// turbo-all
./vendor/bin/sail artisan make:migration create_{features}_table
./vendor/bin/sail artisan make:model {Feature} -f
./vendor/bin/sail artisan migrate
```

### Phase 2: Backend
1. Buat Requests (extend `SimpleCrud*Request` classes)
2. Buat Resources (extend `SimpleCrudResource/Collection`)
3. Buat Actions (extend `SimpleCrudIndexAction`, `SimpleCrudExportAction`)
4. Buat Export (extend `SimpleCrudExport`)
5. Buat Controller (instantiate actions directly, no DI needed)
6. Definisikan routes, include di `routes/web.php`

### Phase 3: Frontend
1. Tambah config di `resources/js/utils/entityConfigs.ts`:
```typescript
export const featureConfig = createSimpleEntityConfig({
    entityName: 'Feature',
    entityNamePlural: 'Features',
    apiBase: 'features',
    filterPlaceholder: 'Search features...',
});
```

2. Buat halaman di `resources/js/pages/{features}/index.tsx`:
```typescript
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { featureConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(featureConfig);
```

### Phase 4: Testing
```bash
// turbo-all
./vendor/bin/sail test --filter={Feature}
./vendor/bin/sail npm run test:e2e tests/e2e/{features}
```

---

## ✅ Verification

```bash
// turbo-all
./vendor/bin/sail artisan migrate
./vendor/bin/sail test --filter={Feature}
```

Gunakan `mcp_laravel-boost_list-routes` untuk verify routes terdaftar.

---

## 📋 Checklist Sebelum PR

- [ ] Model dengan `$fillable` yang benar
- [ ] Migration dengan kolom yang sesuai
- [ ] Semua Requests extend base class
- [ ] Semua Resources extend base class
- [ ] Actions extend base class
- [ ] Export extend base class
- [ ] Controller tanpa dependency injection FilterService
- [ ] Config ditambahkan di `entityConfigs.ts`
- [ ] Frontend page menggunakan `createEntityCrudPage`
- [ ] Tests menggunakan `SimpleCrudTestTrait`
- [ ] Semua tests pass
