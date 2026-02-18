---
name: Feature CRUD Complex
description: Workflow untuk membuat fitur CRUD kompleks dengan relasi foreign key, filter multi-field, dan komponen frontend terpisah.
---

# Feature CRUD Complex

Gunakan skill ini untuk fitur CRUD kompleks dengan relasi antar tabel, filter advanced, dan komponen React modular.

> **📚 Referensi:** Ikuti pola dari modul Employee, Supplier, Customer  
> **📄 Analisis lengkap:** `@/brain/.../complex_crud_analysis.md`

---

## 🎯 Kapan Menggunakan Skill Ini?

```
Modul ini COMPLEX jika memenuhi minimal 2 dari:
├── ✅ Punya relasi foreign key (belongsTo)
├── ✅ Butuh filter dropdown/range (salary, date, status)
├── ✅ Form kompleks → perlu komponen terpisah
└── ✅ Ada logic bisnis tambahan (DTO, custom Actions)
```

**Contoh modul complex:** `employees` (3 FK + salary range + date range), `suppliers` (2 FK + status), `customers` (2 FK + status + notes)

---

## � Variasi Modul Complex (Employee vs Supplier vs Customer)

Ketiga modul memiliki **core pattern yang sama**, tapi dengan perbedaan di:

### Tabel Perbandingan

| Aspek | Employee | Supplier | Customer |
|-------|----------|----------|----------|
| **Foreign Keys** | department_id, position_id, branch_id | branch_id, category_id | branch_id, category_id |
| **Unique Fields** | salary (decimal), hire_date (date) | address (text) | address (text), notes (text) |
| **Range Filters** | ✅ salary_min/max, hire_date_from/to | ❌ | ❌ |
| **Status Field** | ❌ | ✅ enum('active','inactive') | ✅ enum('active','inactive') |
| **Complexity** | ⭐⭐⭐ (paling kompleks) | ⭐⭐ | ⭐⭐ |

### Kapan Menggunakan Pattern Mana?

```
Gunakan Employee pattern jika:
├── Ada 3+ foreign keys
├── Butuh range filters (salary, date)
└── Field numerik/tanggal yang perlu filtering

Gunakan Supplier/Customer pattern jika:
├── Ada 2 foreign keys
├── Butuh status enum (active/inactive)
├── Lebih banyak text fields (address, notes)
└── Filter lebih sederhana (dropdown only)
```

**💡 Tips:** Mulai dengan Supplier/Customer pattern untuk modul sederhana, upgrade ke Employee pattern jika butuh range filtering.

---

## �📁 Struktur File Lengkap

### Backend (Laravel)
```
app/
├── Models/{Feature}.php           # Model dengan relationships
├── Http/
│   ├── Controllers/{Feature}Controller.php
│   ├── Requests/{Features}/
│   │   ├── IndexEmployeeRequest.php
│   │   ├── StoreEmployeeRequest.php
│   │   ├── UpdateEmployeeRequest.php
│   │   └── ExportEmployeeRequest.php
│   └── Resources/{Features}/
│       ├── {Feature}Resource.php
│       └── {Feature}Collection.php
├── Actions/{Features}/
│   ├── Index{Features}Action.php
│   └── Export{Features}Action.php
├── Domain/{Features}/
│   └── {Feature}FilterService.php  # Extended dengan applyAdvancedFilters()
├── DTOs/{Features}/
│   └── Update{Feature}Data.php     # DTO untuk partial updates
└── Exports/{Feature}Export.php

routes/{feature}.php                # Dedicated route file
```

### Frontend (React/Inertia)
```
resources/js/
├── pages/{features}/index.tsx      # Just: export default createEntityCrudPage(config)
├── components/{features}/
│   ├── {Feature}Form.tsx           # Separated form component
│   ├── {Feature}Filters.tsx        # Filter fields definition
│   ├── {Feature}Columns.tsx        # Table column definitions
│   └── {Feature}ViewModal.tsx      # View/detail modal
└── utils/
    └── entityConfigs.ts            # Add config using createComplexEntityConfig()
```

### Testing
```
tests/
├── Feature/{Features}/
│   ├── {Feature}ControllerTest.php       # Controller integration tests
│   └── {Feature}ExportTest.php           # Export functionality tests
├── Unit/
│   ├── Models/{Feature}Test.php          # Model tests
│   ├── Actions/{Features}/*Test.php
│   ├── Domain/{Features}/*Test.php
│   ├── Requests/{Features}/*Test.php
│   └── Resources/{Features}/*Test.php
└── e2e/{features}/
    ├── helpers.ts                        # Module-specific helper functions
    └── {feature}.spec.ts                 # Uses generateModuleTests()
```

> **PENTING:** Group annotation `->group('{features}')` di SEMUA test files (kebab-case).

---

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Lihat relasi antar tabel existing |
| `mcp_laravel-boost_tinker` | Test query relationships |
| `mcp_laravel-boost_list-routes` | Verifikasi routes |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Eloquent relationships |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI (select, date-picker) |
| `mcp_filesystem_read_multiple_files` | Baca referensi dari Employee/Supplier/Customer |

---

## 📖 Referensi Pattern (WAJIB DIBACA)

**ALWAYS** baca file referensi sebelum membuat modul baru:

```bash
# Backend References
mcp_filesystem_read_file("app/Models/Employee.php")
mcp_filesystem_read_file("app/Http/Controllers/EmployeeController.php")
mcp_filesystem_read_file("app/Actions/Employees/IndexEmployeesAction.php")
mcp_filesystem_read_file("app/Domain/Employees/EmployeeFilterService.php")
mcp_filesystem_read_file("app/DTOs/Employees/UpdateEmployeeData.php")
mcp_filesystem_read_file("app/Exports/EmployeeExport.php")

# Frontend References
mcp_filesystem_read_file("resources/js/components/employees/EmployeeForm.tsx")
mcp_filesystem_read_file("resources/js/components/employees/EmployeeFilters.tsx")
mcp_filesystem_read_file("resources/js/components/employees/EmployeeColumns.tsx")
mcp_filesystem_read_file("resources/js/utils/entityConfigs.ts")  # Lihat employeeConfig

# Test Examples
mcp_filesystem_read_file("tests/Feature/EmployeeControllerTest.php")
```

---

## 🚀 Workflow Implementasi

### Phase 1: Database & Model

#### 1.1. Buat Migration
```bash
// turbo
./vendor/bin/sail artisan make:migration create_{features}_table
```

**Template Migration (Employee Pattern - dengan range fields):**
```php
public function up()
{
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        
        // Foreign keys
        $table->foreignId('department_id')->constrained()->onDelete('cascade');
        $table->foreignId('position_id')->constrained()->onDelete('cascade');
        $table->foreignId('branch_id')->constrained()->onDelete('cascade');
        
        // Specific fields (untuk range filtering)
        $table->decimal('salary', 10, 2);  // Numeric range
        $table->date('hire_date');          // Date range
        
        $table->timestamps();
    });
}
```

**Template Migration (Supplier/Customer Pattern - dengan status enum):**
```php
public function up()
{
    Schema::create('suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->text('address')->nullable();  // Text field
        
        // Foreign keys (lebih sedikit)
        $table->foreignId('branch_id')->constrained()->onDelete('cascade');
        $table->foreignId('category_id')->constrained('supplier_categories')->onDelete('cascade');
        
        // Status enum (standard untuk Supplier/Customer)
        $table->enum('status', ['active', 'inactive'])->default('active');
        
        $table->timestamps();
    });
}

// Untuk Customer, tambahkan field 'notes'
public function up()
{
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->text('address')->nullable();
        
        $table->foreignId('branch_id')->constrained()->onDelete('cascade');
        $table->foreignId('category_id')->constrained('customer_categories')->onDelete('cascade');
        
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->text('notes')->nullable();  // Extra field untuk Customer
        
        $table->timestamps();
    });
}
```

#### 1.2. Buat Model + Factory
```bash
// turbo
./vendor/bin/sail artisan make:model {Feature} -f
```

**Pattern Model (Employee - dengan banyak FK dan field khusus):**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone',
        'department_id', 'position_id', 'branch_id',
        'salary', 'hire_date',
    ];

    protected $casts = [
        'salary' => 'decimal:2',      // For money fields
        'hire_date' => 'datetime',    // For date fields
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships (ALWAYS typed)
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
```

**Pattern Model (Supplier/Customer - dengan status enum):**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'address',
        'branch_id', 'category_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupplierCategory::class);
    }
}

// Customer sama, tambahkan 'notes' di fillable dan relasi ke CustomerCategory
```

#### 1.3. Run Migration
```bash
// turbo
./vendor/bin/sail artisan migrate
```

---

### Phase 2: Backend Layer by Layer

#### 2.1. Requests (Validation)

**StoreRequest:**
```php
namespace App\Http\Requests\{Features};

use Illuminate\Foundation\Http\FormRequest;

class Store{Feature}Request extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:{features},email',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'branch_id' => 'required|exists:branches,id',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive', // For enum
        ];
    }
}
```

**UpdateRequest:**
```php
public function rules(): array
{
    return [
        'name' => 'sometimes|required|string|max:255',
        'email' => ['sometimes', 'required', 'email', Rule::unique('{features}')->ignore($this->{feature})],
        'phone' => 'sometimes|nullable|string|max:20',
        'department_id' => 'sometimes|required|exists:departments,id',
        // ... semua field dengan 'sometimes'
    ];
}
```

**IndexRequest:**
```php
public function rules(): array
{
    return [
        'search' => ['nullable', 'string'],
        'department_id' => ['nullable', 'exists:departments,id'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'salary_min' => ['nullable', 'numeric', 'min:0'],
        'salary_max' => ['nullable', 'numeric', 'min:0'],
        'hire_date_from' => ['nullable', 'date'],
        'hire_date_to' => ['nullable', 'date'],
        'status' => ['nullable', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,department_id,salary,hire_date,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ];
}
```

**ExportRequest:**
```php
// NOTE: Filter keys tanpa '_id' suffix!
public function rules(): array
{
    return [
        'search' => ['nullable', 'string'],
        'department' => ['nullable', 'exists:departments,id'], // 'department', bukan 'department_id'
        'branch' => ['nullable', 'exists:branches,id'],
        'sort_by' => ['nullable', 'string', 'in:name,email,created_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
    ];
}
```

#### 2.2. Resources (API Responses)

**{Feature}Resource.php:**
```php
namespace App\Http\Resources\{Features};

use App\Models\{Feature};
use Illuminate\Http\Resources\Json\JsonResource;

/** @property {Feature} $resource */
class {Feature}Resource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            
            // IMPORTANT: Nested objects dengan {id, name}
            'department' => [
                'id' => $this->resource->department_id,
                'name' => $this->resource->department?->name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            
            'salary' => (string) $this->resource->salary, // Cast decimal to string
            'hire_date' => $this->resource->hire_date?->toIso8601String(),
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
```

**{Feature}Collection.php:**
```php
namespace App\Http\Resources\{Features};

use Illuminate\Http\Resources\Json\ResourceCollection;

class {Feature}Collection extends ResourceCollection
{
    public $collects = {Feature}Resource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
```

#### 2.3. FilterService (Domain Logic)

**Pattern A: Employee (dengan Range Filters)**
```php
namespace App\Domain\Employees;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class EmployeeFilterService
{
    use BaseFilterService; // Provides applySearch(), applySorting()

    /**
     * @param Builder<\App\Models\Employee> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Foreign key filters (banyak)
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Range filters (numeric) - KHUSUS Employee
        if (!empty($filters['salary_min'])) {
            $query->where('salary', '>=', $filters['salary_min']);
        }
        if (!empty($filters['salary_max'])) {
            $query->where('salary', '<=', $filters['salary_max']);
        }

        // Range filters (date) - KHUSUS Employee
        if (!empty($filters['hire_date_from'])) {
            $query->whereDate('hire_date', '>=', $filters['hire_date_from']);
        }
        if (!empty($filters['hire_date_to'])) {
            $query->whereDate('hire_date', '<=', $filters['hire_date_to']);
        }
    }
}
```

**Pattern B: Supplier/Customer (Lebih Sederhana)**
```php
namespace App\Domain\Suppliers;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class SupplierFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\Supplier> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Foreign key filters (lebih sedikit)
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Status enum filter - STANDARD untuk Supplier/Customer
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}

// CustomerFilterService persis sama, ganti namespace dan model saja
```

#### 2.4. DTO (Data Transfer Object)

```php
namespace App\DTOs\{Features};

readonly class Update{Feature}Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?int $department_id = null,
        public ?int $branch_id = null,
        public ?string $salary = null,
        public ?string $hire_date = null,
        public ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            department_id: $data['department_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            salary: $data['salary'] ?? null,
            hire_date: $data['hire_date'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->department_id !== null) $data['department_id'] = $this->department_id;
        if ($this->branch_id !== null) $data['branch_id'] = $this->branch_id;
        if ($this->salary !== null) $data['salary'] = $this->salary;
        if ($this->hire_date !== null) $data['hire_date'] = $this->hire_date;
        if ($this->status !== null) $data['status'] = $this->status;

        return $data; // Only non-null values
    }
}
```

#### 2.5. Actions

**Index{Features}Action.php:**
```php
namespace App\Actions\{Features};

use App\Domain\{Features}\{Feature}FilterService;
use App\Http\Requests\{Features}\Index{Feature}Request;
use App\Models\{Feature};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class Index{Features}Action
{
    public function __construct(
        private {Feature}FilterService $filterService
    ) {}

    public function execute(Index{Feature}Request $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = {Feature}::query()->with(['department', 'branch']); // Eager load

        // Search OR Advanced Filters (mutually exclusive)
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'department_id' => $request->get('department_id'),
                'branch_id' => $request->get('branch_id'),
            ]);
        }

        // Range filters (always applied)
        $this->filterService->applyAdvancedFilters($query, [
            'salary_min' => $request->get('salary_min'),
            'salary_max' => $request->get('salary_max'),
            'hire_date_from' => $request->get('hire_date_from'),
            'hire_date_to' => $request->get('hire_date_to'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'email', 'department_id', 'branch_id', 'salary', 'hire_date', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
```

**Export{Features}Action.php:**
```php
namespace App\Actions\{Features};

use App\Exports\{Feature}Export;
use App\Http\Requests\{Features}\Export{Feature}Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class Export{Features}Action
{
    public function execute(Export{Feature}Request $request): JsonResponse
    {
        $validated = $request->validated();

        // IMPORTANT: Map keys (request uses 'department', not 'department_id')
        $filters = [
            'search' => $validated['search'] ?? null,
            'department' => $validated['department'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters); // Remove nulls

        $filename = '{features}_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new {Feature}Export($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
```

#### 2.6. Export Class

```php
namespace App\Exports;

use App\Models\{Feature};
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class {Feature}Export implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = {Feature}::query()->with(['department', 'branch']);

        // Search
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters (NOTE: keys are 'department', not 'department_id')
        if (!empty($this->filters['department'])) {
            $query->where('department_id', $this->filters['department']);
        }
        if (!empty($this->filters['branch'])) {
            $query->where('branch_id', $this->filters['branch']);
        }

        // Sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['name', 'email', 'department_id', 'salary', 'hire_date', 'created_at'];
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Department', 'Branch', 'Salary', 'Hire Date', 'Status', 'Created At'];
    }

    public function map(${feature}): array
    {
        return [
            ${feature}->id,
            ${feature}->name,
            ${feature}->email,
            ${feature}->phone,
            ${feature}->department?->name,
            ${feature}->branch?->name,
            ${feature}->salary,
            ${feature}->hire_date->format('Y-m-d'),
            ${feature}->status,
            ${feature}->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
```

#### 2.7. Controller

```php
namespace App\Http\Controllers;

use App\Actions\{Features}\{Index{Features}Action, Export{Features}Action};
use App\Domain\{Features}\{Feature}FilterService;
use App\DTOs\{Features}\Update{Feature}Data;
use App\Http\Requests\{Features}\*;
use App\Http\Resources\{Features}\*;
use App\Models\{Feature};
use Illuminate\Http\JsonResponse;

class {Feature}Controller extends Controller
{
    public function index(Index{Feature}Request $request): JsonResponse
    {
        ${features} = (new Index{Features}Action(app({Feature}FilterService::class)))->execute($request);
        return (new {Feature}Collection(${features}))->response();
    }

    public function store(Store{Feature}Request $request): JsonResponse
    {
        ${feature} = {Feature}::create($request->validated());
        return (new {Feature}Resource(${feature}))->response()->setStatusCode(201);
    }

    public function show({Feature} ${feature}): JsonResponse
    {
        ${feature}->load(['department', 'branch']);
        return (new {Feature}Resource(${feature}))->response();
    }

    public function update(Update{Feature}Request $request, {Feature} ${feature}): JsonResponse
    {
        $dto = Update{Feature}Data::fromArray($request->validated());
        ${feature}->update($dto->toArray());
        return (new {Feature}Resource(${feature}))->response();
    }

    public function export(Export{Feature}Request $request, Export{Features}Action $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function destroy({Feature} ${feature}): JsonResponse
    {
        ${feature}->delete();
        return response()->json(null, 204);
    }
}
```

#### 2.8. Routes

```php
// routes/{feature}.php
use App\Http\Controllers\{Feature}Controller;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/{features}', [{Feature}Controller::class, 'index']);
    Route::post('/{features}', [{Feature}Controller::class, 'store']);
    Route::get('/{features}/{{feature}}', [{Feature}Controller::class, 'show']);
    Route::put('/{features}/{{feature}}', [{Feature}Controller::class, 'update']);
    Route::delete('/{features}/{{feature}}', [{Feature}Controller::class, 'destroy']);
    Route::post('/{features}/export', [{Feature}Controller::class, 'export']);
});
```

---

### Phase 3: Frontend Components

#### 3.1. Page Component

```tsx
// resources/js/pages/{features}/index.tsx
'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { {feature}Config } from '@/utils/entityConfigs';

export default createEntityCrudPage({feature}Config);
```

#### 3.2. Form Component

> **Variasi:** Employee form lebih kompleks (3 AsyncSelectField + DatePicker + numeric salary).  
> Supplier/Customer form lebih sederhana (2 AsyncSelectField + textarea + SelectField untuk status).

**Pattern A: Employee Form (Kompleks)**
```tsx
// resources/js/components/{features}/{Feature}Form.tsx
'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { SelectField } from '@/components/common/SelectField';
import NameField from '@/components/common/NameField';

import { {Feature}, {Feature}FormData } from '@/types/entity';
import { {feature}FormSchema } from '@/utils/schemas';

interface {Feature}FormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    {feature}?: {Feature} | null;
    onSubmit: (data: {Feature}FormData) => void;
    isLoading?: boolean;
}

// Section functions for organization
const renderBasicInfoSection = () => (
    <>
        <NameField name="name" label="Name" placeholder="John Doe" />
        <InputField name="email" label="Email" type="email" placeholder="john@example.com" />
        <InputField name="phone" label="Phone" placeholder="+1 (555) 123-4567" />
    </>
);

const renderRelationshipSection = () => (
    <>
        <AsyncSelectField
            name="department_id"
            label="Department"
            url="/api/departments"
            placeholder="Select a department"
        />
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
    </>
);

const renderSpecificFieldsSection = () => (
    <>
        <InputField
            name="salary"
            label="Salary"
            type="number"
            placeholder="50000"
        />
        <DatePickerField
            name="hire_date"
            label="Hire Date"
        />
        <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
            ]}
        />
    </>
);

const get{Feature}FormDefaults = ({feature}?: {Feature} | null): {Feature}FormData => {
    if (!{feature}) {
        return {
            name: '',
            email: '',
            phone: '',
            department_id: '',
            branch_id: '',
            salary: '',
            hire_date: '',
            status: 'active',
        };
    }

    return {
        name: {feature}.name,
        email: {feature}.email,
        phone: {feature}.phone || '',
        // Handle nested objects from API
        department_id: typeof {feature}.department === 'object'
            ? String({feature}.department.id)
            : String({feature}.department),
        branch_id: typeof {feature}.branch === 'object'
            ? String({feature}.branch.id)
            : String({feature}.branch),
        salary: {feature}.salary || '',
        hire_date: {feature}.hire_date || '',
        status: {feature}.status,
    };
};

export const {Feature}Form = memo<{Feature}FormProps>(function {Feature}Form({
    open,
    onOpenChange,
    {feature},
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => get{Feature}FormDefaults({feature}),
        [{feature}],
    );

    const form = useForm<{Feature}FormData>({
        resolver: zodResolver({feature}FormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<{Feature}FormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={{feature} ? 'Edit {Feature}' : 'Add New {Feature}'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderRelationshipSection()}
            {renderSpecificFieldsSection()}
        </EntityForm>
    );
});
```

**Pattern B: Supplier/Customer Form (Lebih Sederhana)**
```tsx
// resources/js/components/suppliers/SupplierForm.tsx
'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { SelectField } from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import NameField from '@/components/common/NameField';

import { Supplier, SupplierFormData } from '@/types/entity';
import { supplierFormSchema } from '@/utils/schemas';

interface SupplierFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    supplier?: Supplier | null;
    onSubmit: (data: SupplierFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
        <NameField name="name" label="Name" placeholder="Supplier Name" />
        <InputField name="email" label="Email" type="email" placeholder="supplier@example.com" />
        <InputField name="phone" label="Phone" placeholder="+1 (555) 123-4567" />
    </>
);

const renderAddressSection = () => (
    <TextareaField
        name="address"
        label="Address"
        placeholder="Enter supplier address"
        rows={2}
    />
);

const renderDetailsSection = () => (
    <>
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <AsyncSelectField
            name="category_id"
            label="Category"
            url="/api/supplier-categories"
            placeholder="Select a category"
        />
        <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
            ]}
        />
    </>
);

const getSupplierFormDefaults = (supplier?: Supplier | null): SupplierFormData => {
    if (!supplier) {
        return {
            name: '',
            email: '',
            phone: '',
            address: '',
            branch_id: '',
            category_id: '',
            status: 'active',
        };
    }

    return {
        name: supplier.name,
        email: supplier.email,
        phone: supplier.phone || '',
        address: supplier.address || '',
        branch_id: typeof supplier.branch === 'object'
            ? String(supplier.branch.id)
            : String(supplier.branch_id),
        category_id: typeof supplier.category === 'object'
            ? String(supplier.category.id)
            : String(supplier.category_id),
        status: supplier.status,
    };
};

export const SupplierForm = memo<SupplierFormProps>(function SupplierForm({
    open,
    onOpenChange,
    supplier,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getSupplierFormDefaults(supplier),
        [supplier],
    );

    const form = useForm<SupplierFormData>({
        resolver: zodResolver(supplierFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<SupplierFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={supplier ? 'Edit Supplier' : 'Add New Supplier'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderAddressSection()}
            {renderDetailsSection()}
        </EntityForm>
    );
});

// Untuk Customer, tambahkan renderNotesSection() dengan TextareaField untuk notes
```

#### 3.3. Filter Fields

**Pattern A: Employee Filters (dengan Range Filters)**
```tsx
// resources/js/components/employees/EmployeeFilters.tsx
import { type FieldDescriptor } from '@/components/common/filters';

export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            type: 'text',
            placeholder: 'Search by name, email, or phone...',
        },
        // Banyak FK dropdowns
        {
            name: 'department_id',
            label: 'Department',
            type: 'select-async',
            placeholder: 'All Departments',
            url: '/api/departments',
        },
        {
            name: 'position_id',
            label: 'Position',
            type: 'select-async',
            placeholder: 'All Positions',
            url: '/api/positions',
        },
        {
            name: 'branch_id',
            label: 'Branch',
            type: 'select-async',
            placeholder: 'All Branches',
            url: '/api/branches',
        },
        // Range filters - KHUSUS Employee
        {
            name: 'salary_min',
            label: 'Min Salary',
            type: 'number',
            placeholder: '0',
        },
        {
            name: 'salary_max',
            label: 'Max Salary',
            type: 'number',
            placeholder: '100000',
        },
        {
            name: 'hire_date_from',
            label: 'Hired From',
            type: 'date',
        },
        {
            name: 'hire_date_to',
            label: 'Hired To',
            type: 'date',
        },
    ];
}
```

**Pattern B: Supplier/Customer Filters (Lebih Sederhana)**
```tsx
// resources/js/components/suppliers/SupplierFilters.tsx
import { type FieldDescriptor } from '@/components/common/filters';

export function createSupplierFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            type: 'text',
            placeholder: 'Search by name, email, or phone...',
        },
        // Hanya 2 FK dropdowns
        {
            name: 'branch_id',
            label: 'Branch',
            type: 'select-async',
            placeholder: 'All Branches',
            url: '/api/branches',
        },
        {
            name: 'category_id',
            label: 'Category',
            type: 'select-async',
            placeholder: 'All Categories',
            url: '/api/supplier-categories',
        },
        // Status enum - STANDARD untuk Supplier/Customer
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            placeholder: 'All Statuses',
            options: [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
            ],
        },
    ];
}

// CustomerFilters sama persis, ganti URL category ke '/api/customer-categories'
```

#### 3.4. Table Columns

```tsx
// resources/js/components/{features}/{Feature}Columns.tsx
import { type EntityColumnDef } from '@/components/common/EntityDataTable';
import { type {Feature} } from '@/types/entity';

export const {feature}Columns: EntityColumnDef<{Feature}>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'email',
        header: 'Email',
    },
    {
        accessorKey: 'phone',
        header: 'Phone',
    },
    {
        accessorKey: 'department.name',
        header: 'Department',
    },
    {
        accessorKey: 'branch.name',
        header: 'Branch',
    },
    {
        accessorKey: 'salary',
        header: 'Salary',
        cell: ({ row }) => `$${row.original.salary}`,
    },
    {
        accessorKey: 'hire_date',
        header: 'Hire Date',
        cell: ({ row }) => new Date(row.original.hire_date).toLocaleDateString(),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <span className={row.original.status === 'active' ? 'text-green-600' : 'text-red-600'}>
                {row.original.status}
            </span>
        ),
    },
];
```

#### 3.5. ViewModal (Optional)

```tsx
// resources/js/components/{features}/{Feature}ViewModal.tsx
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type {Feature} } from '@/types/entity';

interface {Feature}ViewModalProps {
    open: boolean;
    onClose: () => void;
    item: {Feature} | null;
}

export function {Feature}ViewModal({ open, onClose, item }: {Feature}ViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <span className="font-semibold">Email:</span> {item.email}
                    </div>
                    <div>
                        <span className="font-semibold">Phone:</span> {item.phone || 'N/A'}
                    </div>
                    <div>
                        <span className="font-semibold">Department:</span> {item.department.name}
                    </div>
                    <div>
                        <span className="font-semibold">Branch:</span> {item.branch.name}
                    </div>
                    <div>
                        <span className="font-semibold">Salary:</span> ${item.salary}
                    </div>
                    <div>
                        <span className="font-semibold">Hire Date:</span> {new Date(item.hire_date).toLocaleDateString()}
                    </div>
                    <div>
                        <span className="font-semibold">Status:</span> {item.status}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
```

#### 3.6. Entity Config

```typescript
// Add to resources/js/utils/entityConfigs.ts

import { {feature}Columns } from '@/components/{features}/{Feature}Columns';
import { create{Feature}FilterFields } from '@/components/{features}/{Feature}Filters';
import { {Feature}Form } from '@/components/{features}/{Feature}Form';
import { {Feature}ViewModal } from '@/components/{features}/{Feature}ViewModal';

export const {feature}Config = createComplexEntityConfig({
    entityName: '{Feature}',
    entityNamePlural: '{Features}',
    apiEndpoint: '/api/{features}',
    exportEndpoint: '/api/{features}/export',
    queryKey: ['{features}'],
    breadcrumbs: [{ title: '{Features}', href: '/{features}' }],
    initialFilters: {
        search: '',
        department_id: '',
        branch_id: '',
        status: '',
    },
    columns: {feature}Columns,
    filterFields: create{Feature}FilterFields(),
    formComponent: {Feature}Form,
    formType: 'complex',
    entityNameForSearch: '{feature}',
    viewModalComponent: {Feature}ViewModal,
    getDeleteMessage: ({feature}: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${${feature}.name}'s {feature} record.`,
});
```

---

### Phase 4: Testing

#### 4.1. Pest Tests

Lokasi test files sesuai `tests/REFACTORING_PLAN.md`:
- Feature: `tests/Feature/{Features}/{Feature}ControllerTest.php`
- Feature Export: `tests/Feature/{Features}/{Feature}ExportTest.php`
- Unit Model: `tests/Unit/Models/{Feature}Test.php`
- Unit lainnya: `tests/Unit/{Layer}/{Features}/`

Group annotation `->group('{features}')` di SEMUA file (kebab-case).

Buat tests following pattern dari `tests/Feature/Employees/EmployeeControllerTest.php`.

```bash
// turbo
./vendor/bin/sail test --group {features}
```

#### 4.2. E2E Tests

Lokasi sesuai `tests/e2e/REFACTORING_PLAN.md`:
- Helpers: `tests/e2e/{features}/helpers.ts`
- Spec: `tests/e2e/{features}/{feature}.spec.ts`

Menggunakan `generateModuleTests()` dari `tests/e2e/shared-test-factories.ts`.
Helper functions WAJIB di file terpisah per-modul, bukan di `helpers.ts` global.

```bash
// turbo
npx playwright test tests/e2e/{features}/
```

---

## ✅ Verification Checklist

Before marking complete:

**Backend:**
- [ ] Model has relationships with typed returns
- [ ] FilterService extends with `applyAdvancedFilters()`
- [ ] DTO created for updates with `fromArray()` and `toArray()`
- [ ] Resources return nested objects `{id, name}` for FKs
- [ ] Export uses correct filter key names (without `_id`)
- [ ] Controller uses Actions for index/export
- [ ] Routes created in `routes/{feature}.php`

**Frontend:**
- [ ] Page uses `createEntityCrudPage(config)`
- [ ] Form uses `AsyncSelectField` for FKs
- [ ] Form has section render functions
- [ ] Filters use `create{Feature}FilterFields()`
- [ ] Columns defined with proper accessors
- [ ] Entity config uses `createComplexEntityConfig()`

**Testing:**
- [ ] Feature tests di `tests/Feature/{Features}/` subfolder
- [ ] Unit tests di `tests/Unit/{Layer}/{Features}/`
- [ ] Group annotation `->group('{features}')` di SEMUA test files
- [ ] E2E test menggunakan `generateModuleTests()` dari `shared-test-factories.ts`
- [ ] E2E helpers di `tests/e2e/{features}/helpers.ts` (bukan global)
- [ ] All tests pass

**Running Tests:**
```bash
// turbo-all
./vendor/bin/sail test --group {features}
npx playwright test tests/e2e/{features}/
```

---

## 🚨 Common Pitfalls

1. ❌ **Export filter mismatch** - Export request uses `'branch'`, not `'branch_id'`
2. ❌ **Missing DTO** - Always use DTO for update operations
3. ❌ **Forgetting eager loading** - Always `->with([...])` in queries
4. ❌ **Inconsistent Resources** - Always nest FKs as `{id, name}` objects
5. ❌ **Form type mismatch** - Handle both nested objects and IDs in form defaults

---

**End of SKILL.md**

