---
name: Feature Custom / Non-CRUD
description: Workflow untuk fitur non-standar seperti Dashboard, Settings, User Management, atau Halaman Khusus.
---

# Feature Custom / Non-CRUD

Gunakan skill ini untuk halaman atau fitur yang tidak mengikuti pola CRUD standar.

> **📚 Referensi:** Ikuti pola dari modul User dan Permission  
> **📄 Analisis lengkap:** Lihat implementasi di `app/Http/Controllers/UserController.php` dan `PermissionController.php`
> **🪖 Catatan Helmet:** Untuk meta tags, tetap gunakan import `react-helmet-async`, tetapi resolver project mengarahkannya ke shim lokal `resources/js/lib/react-helmet-async.tsx`. Jangan menambah dependency upstream lagi tanpa verifikasi kompatibilitas React 19.

---

## 🎯 Kapan Menggunakan Skill Ini?

```
Fitur ini NON-CRUD jika:
├── ❌ TIDAK ada model baru untuk di-CRUD
├── ✅ Bekerja dengan model existing (relasi/linking)
├── ✅ Custom UI (dashboard, matrix, wizard, management)
├── ✅ Routing TIDAK pakai Route::resource
└── ✅ Operations: sync, bulk update, linking, aggregation
```

**Contoh Non-CRUD**: `users` (link user to employee), `permissions` (bulk assign), `dashboard` (aggregation)  
**Bukan Non-CRUD**: `employees`, `customers` (ini Complex CRUD)

---

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Lihat model/tabel existing untuk relasi |
| `mcp_laravel-boost_list-routes` | Lihat routes existing, plan custom routes |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi React Query, react-router-dom |
| `mcp_laravel-boost_tinker` | Test relationships dan business logic |
| `mcp_shadcn-ui-mcp-server_list_blocks` | Cari dashboard/UI blocks |
| `mcp_shadcn-ui-mcp-server_get_block` | Ambil complex UI blocks |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI (combobox, tree-view) |
| `mcp_filesystem_read_multiple_files` | Baca file referensi User/Permission |

---

## 📊 Pattern Types

Terdapat 2 pattern utama untuk Non-CRUD modules:

### Pattern A: Parent-Child Management (User Module)

**Karakteristik:**
- ✅ Manage child entity via parent (Employee → User)
- ✅ Create/update child through parent endpoint
- ✅ Custom validation (conditional rules)
- ✅ Transaction-based operations

**Kapan Gunakan:**
```
Gunakan Pattern A jika:
├── Ada parent-child relationship (hasOne, hasMany)
├── Child creation/update HARUS via parent
├── Ada conditional logic (create vs update)
└── Perlu transaction safety
```

**Contoh Use Cases:**
- User Management (Employee → User)
- Contact Management (Customer → Contact)
- Settings Management (Organization → Settings)

### Pattern B: Matrix/Bulk Operations (Permission Module)

**Karakteristik:**
- ✅ Many-to-many relationship management
- ✅ Bulk sync operations
- ✅ Matrix/tree view UI
- ✅ No entity creation, only linking

**Kapan Gunakan:**
```
Gunakan Pattern B jika:
├── Many-to-many relationship (belongsToMany)
├── Bulk assignment/sync needed
├── Matrix view atau tree hierarchy
└── Read + bulk update only (no create)
```

**Contoh Use Cases:**
- Permission Assignment (Employee ↔ Permission)
- Role Management (User ↔ Role)
- Tag Assignment (Product ↔ Tag)

---

## 📁 Struktur File Lengkap

### Pattern A: Parent-Child Management

```
Backend:
├── app/Actions/{Features}/
│   └── Sync{Feature}For{Parent}Action.php   # Business logic untuk sync
├── app/Http/Controllers/{Feature}Controller.php
├── app/Http/Requests/{Features}/
│   └── Update{Feature}Request.php          # Conditional validation
├── app/Http/Resources/{Features}/
│   └── {Feature}Resource.php               # Simple resource
└── routes/api/{feature}.php

Frontend:
├── resources/js/pages/{features}/index.tsx
└── resources/js/components/{features}/
    ├── {Feature}Form.tsx                   # Reusable form component
    └── {Parent}SearchModal.tsx             # Optional: untuk search parent

Tests:
├── tests/Feature/{Feature}ControllerTest.php
└── tests/Unit/Requests/{Features}/Update{Feature}RequestTest.php
```

### Pattern B: Matrix/Bulk Operations

```
Backend:
├── app/Http/Controllers/{Feature}Controller.php
│   └── index() method returns JSON data
└── routes/api/{feature}.php

Frontend:
├── resources/js/pages/{features}/index.tsx
└── resources/js/components/{features}/
    ├── {Parent}Selector.tsx                # Select parent entity
    └── {Feature}Manager.tsx                # Matrix/tree view

Tests:
└── tests/Feature/{Feature}ControllerTest.php
```

---

## 📖 Code Examples - Pattern A (User Management)

### Backend Implementation

#### 1. Action (Business Logic)

```php
<?php

namespace App\Actions\Users;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create or update a user for a given employee.
 * 
 * This action handles the business logic for syncing user data
 * with an employee, including conditional password updates.
 */
class SyncUserForEmployeeAction
{
    public function execute(Employee $employee, array $data): User
    {
        return DB::transaction(function () use ($employee, $data) {
            $existingUser = $employee->user;
            
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            // Only update password if provided (optional for updates)
            if (!empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if ($existingUser) {
                // Update existing user
                $existingUser->update($userData);
                $user = $existingUser;
            } else {
                // Create new user and link to employee
                $user = User::create($userData);
                $employee->update(['user_id' => $user->id]);
            }

            return $user;
        });
    }
}
```

**Key Points:**
- ✅ Use DB::transaction() for data consistency
- ✅ Handle both create and update in one action
- ✅ Conditional password update (only if provided)
- ✅ Return the user entity for response

#### 2. Request (Conditional Validation)

```php
<?php

namespace App\Http\Requests\Users;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $existingUser = $employee->user;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($existingUser?->id),
            ],
            // Password is required for NEW users, optional for updates
            'password' => [
                $existingUser ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
        ];
    }
}
```

**Key Points:**
- ✅ Conditional validation based on existing user
- ✅ Use Rule::unique()->ignore() for email updates
- ✅ Password required for creation, optional for update

#### 3. Controller

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Users\SyncUserForEmployeeAction;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

/**
 * Controller for user management operations.
 *
 * Handles user creation, updates, and linking users to employees.
 * Note: Frontend page routing ditangani oleh react-router-dom.
 */
class UserController extends Controller
{
    /**
     * Get user data for an employee.
     *
     * Returns the user linked to the employee, or null if no user is linked.
     */
    public function getUserByEmployee(Employee $employee): JsonResponse
    {
        $user = $employee->user;

        return response()->json([
            'user' => $user ? new UserResource($user) : null,
            'employee' => [
                'name' => $employee->name,
                'email' => $employee->email,
            ],
        ]);
    }

    /**
     * Create or update user for an employee.
     */
    public function updateUser(
        UpdateUserRequest $request, 
        Employee $employee
    ): JsonResponse {
        $user = (new SyncUserForEmployeeAction())->execute(
            $employee, 
            $request->validated()
        );

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => new UserResource($user),
        ]);
    }
}
```

**Key Points:**
- ✅ API-only controller (frontend page routing via react-router-dom)
- ✅ GET endpoint untuk fetch data
- ✅ POST endpoint untuk sync (create/update)
- ✅ Action instantiation tanpa DI (consistent dengan codebase)

#### 4. Routes

```php
<?php

// routes/api/users.php
// Frontend routing ditangani oleh react-router-dom di app-routes.tsx
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// API routes (nested under parent resource)
Route::middleware('permission:user,true')->group(function () {
    Route::get('employees/{employee}/user', [UserController::class, 'getUserByEmployee']);
    Route::post('employees/{employee}/user', [UserController::class, 'updateUser']);
});
```

**Key Points:**
- ✅ Nested resource routes (`/api/employees/{employee}/user`)
- ✅ Permission middleware for API
- ✅ Frontend route di `app-routes.tsx`, BUKAN di sini

#### 5. Resource

```php
<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

**Key Points:**
- ✅ Simple resource (no nested relationships)
- ✅ ISO date format untuk consistency

---

### Frontend Implementation - Pattern A

#### 1. Main Page

```tsx
'use client';

import { useUserManagement } from '@/hooks/useUserManagement';
import { UserForm } from '@/components/users/UserForm';
import AppLayout from '@/layouts/app-layout';
import { Helmet } from 'react-helmet-async';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FormProvider } from 'react-hook-form';

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'User Management' },
];

export default function UsersPage() {
    const {
        form,
        loading,
        userExists,
        errors,
        handleSaveUser,
    } = useUserManagement();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet><title>User Management</title></Helmet>
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>User Management</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <FormProvider {...form}>
                            <UserForm
                                loading={loading}
                                userExists={userExists}
                                errors={errors}
                                onSave={handleSaveUser}
                            />
                        </FormProvider>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
```

**Key Points:**
- ✅ Full page implementation (TIDAK pakai `createEntityCrudPage`)
- ✅ Custom hook untuk state management
- ✅ FormProvider untuk form context

#### 2. Form Component

```tsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Zap } from 'lucide-react';
import { useFormContext } from 'react-hook-form';

interface UserFormProps {
    loading: boolean;
    userExists: boolean;
    errors: {
        name?: string[];
        email?: string[];
        password?: string[];
    };
    onSave: () => void;
}

export function UserForm({
    loading,
    userExists,
    errors,
    onSave,
}: UserFormProps) {
    const { register } = useFormContext();

    return (
        <div className="max-w-md space-y-4">
            <div className="space-y-2">
                <Label htmlFor="name">User Name</Label>
                <Input
                    id="name"
                    type="text"
                    {...register('name')}
                    placeholder="Enter user name"
                    disabled={loading}
                />
                {errors.name && (
                    <p className="text-sm text-destructive">{errors.name[0]}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="email">User Email</Label>
                <Input
                    id="email"
                    type="email"
                    {...register('email')}
                    placeholder="Enter user email"
                    disabled={loading}
                />
                {errors.email && (
                    <p className="text-sm text-destructive">
                        {errors.email[0]}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password">
                    User Password{' '}
                    {!userExists && <span className="text-destructive">*</span>}
                </Label>
                <Input
                    id="password"
                    type="password"
                    {...register('password')}
                    placeholder={
                        userExists
                            ? 'Leave empty to keep current password'
                            : 'Enter password (required for new user)'
                    }
                    disabled={loading}
                />
                {errors.password && (
                    <p className="text-sm text-destructive">
                        {errors.password[0]}
                    </p>
                )}
            </div>

            <Button onClick={onSave} disabled={loading} className="w-full">
                {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                Save Changes
            </Button>
        </div>
    );
}
```

**Key Points:**
- ✅ Reusable component dengan props
- ✅ Conditional UI (password required indicator)
- ✅ Error handling per field
- ✅ Loading state dengan icon

#### 3. Custom Hook (Example Structure)

```tsx
import { useForm } from 'react-hook-form';
import { useState } from 'react';
import axios from '@/lib/axios';

export function useUserManagement() {
    const form = useForm();
    const [loading, setLoading] = useState(false);
    const [userExists, setUserExists] = useState(false);
    const [errors, setErrors] = useState({});

    const fetchUser = async (employeeId: number) => {
        // Fetch user data via API
        const response = await fetch(`/api/employees/${employeeId}/user`);
        const data = await response.json();
        
        if (data.user) {
            form.reset(data.user);
            setUserExists(true);
        }
    };

    const handleSaveUser = async () => {
        const employeeId = form.getValues('employee_id');
        
        setLoading(true);
        try {
            const response = await axios.post(
                `/api/employees/${employeeId}/user`,
                form.getValues()
            );
            setErrors({});
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }
        } finally {
            setLoading(false);
        }
    };

    return {
        form,
        loading,
        userExists,
        errors,
        fetchUser,
        handleSaveUser,
    };
}
```

**Key Points:**
- ✅ Encapsulate form logic
- ✅ API calls untuk fetch data
- ✅ Error handling
- ✅ Loading state management

---

## 📖 Code Examples - Pattern B (Permission Management)

### Backend Implementation

#### 1. Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;

/**
 * Controller for permission management operations.
 * Note: Frontend page rendering ditangani oleh react-router-dom.
 */
class PermissionController extends Controller
{
    /**
     * Get all permissions as JSON.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('id')->get();

        return response()->json([
            'permissions' => $permissions,
        ]);
    }
}
```

**Key Points:**
- ✅ Return JSON (bukan Inertia::render)
- ✅ Data di-fetch oleh frontend via React Query

#### 2. Routes

```php
<?php

// routes/api/permissions.php
// Frontend routing ditangani oleh react-router-dom di app-routes.tsx
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

// API routes
Route::middleware('permission:permission,true')->group(function () {
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
    Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
});
```

**Key Points:**
- ✅ API-only routes (no frontend route di backend)
- ✅ Sync operations di parent controller (EmployeeController)

---

### Frontend Implementation - Pattern B

#### 1. Main Page

```tsx
'use client';

import { useForm, FormProvider } from 'react-hook-form';
import { usePermissionManagement } from '@/hooks/usePermissionManagement';
import { EmployeeSelector } from '@/components/permissions/EmployeeSelector';
import { PermissionManager } from '@/components/permissions/PermissionManager';
import AppLayout from '@/layouts/app-layout';
import { Helmet } from 'react-helmet-async';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from '@/lib/axios';

export default function PermissionsPage() {
    // Fetch permissions via React Query (bukan Inertia props)
    const { data: permissionsData } = useQuery({
        queryKey: ['permissions'],
        queryFn: async () => {
            const { data } = await axios.get('/api/permissions');
            return data.permissions;
        },
    });
    const permissions = permissionsData ?? [];

    const form = useForm();
    const {
        loading,
        selectedPermissions,
        setSelectedPermissions,
        fetchPermissions,
        updatePermissions,
    } = usePermissionManagement();

    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        if (selectedEmployeeId) {
            fetchPermissions(selectedEmployeeId);
        } else {
            setSelectedPermissions([]);
        }
    }, [selectedEmployeeId, fetchPermissions, setSelectedPermissions]);

    const handleSave = () => {
        updatePermissions(selectedEmployeeId, selectedPermissions);
    };

    return (
        <AppLayout>
            <Helmet><title>Permissions</title></Helmet>
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Permissions Hierarchy</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="max-w-md space-y-4">
                            <FormProvider {...form}>
                                <form className="space-y-4">
                                    <EmployeeSelector />
                                </form>
                            </FormProvider>
                        </div>

                        {selectedEmployeeId && (
                            <PermissionManager
                                permissions={permissions}
                                selectedPermissions={selectedPermissions}
                                onSelectionChange={setSelectedPermissions}
                                onSave={handleSave}
                                loading={loading}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
```

**Key Points:**
- ✅ Data di-fetch via React Query (bukan Inertia props)
- ✅ Watch employee selection untuk trigger fetch
- ✅ Conditional rendering (show manager setelah select)

#### 2. Employee Selector Component

```tsx
import AsyncSelectField from '@/components/common/AsyncSelectField';
import { useFormContext } from 'react-hook-form';

export function EmployeeSelector() {
    const { control } = useFormContext();

    return (
        <AsyncSelectField
            name="employee_id"
            label="Select Employee"
            url="/api/employees"
            placeholder="Search for an employee..."
            labelFn={(item) => item.name}
            valueFn={(item) => String(item.id)}
        />
    );
}
```

**Key Points:**
- ✅ Reuse AsyncSelectField component
- ✅ Simple wrapper dengan specific config

#### 3. Permission Manager Component

```tsx
import { TreeView } from '@/components/tree/tree-view';
import { Button } from '@/components/ui/button';
import { Permission } from '@/types/permission';
import { Zap } from 'lucide-react';

interface PermissionManagerProps {
    permissions: Permission[];
    selectedPermissions: number[];
    onSelectionChange: (ids: number[]) => void;
    onSave: () => void;
    loading: boolean;
}

export function PermissionManager({
    permissions,
    selectedPermissions,
    onSelectionChange,
    onSave,
    loading,
}: PermissionManagerProps) {
    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Permissions</h3>
                <Button
                    onClick={onSave}
                    disabled={loading}
                    data-testid="save-permissions-btn"
                >
                    {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                    Save Changes
                </Button>
            </div>
            <div className="rounded-md border p-4">
                <TreeView
                    data={permissions}
                    selectedIds={selectedPermissions}
                    onSelectionChange={onSelectionChange}
                />
            </div>
        </div>
    );
}
```

**Key Points:**
- ✅ Controlled component (external state)
- ✅ TreeView untuk hierarchical data
- ✅ Integrated save button

---

## 🧪 Testing Patterns

### Pattern A: Parent-Child Tests (User Module)

```php
<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('users');

/**
 * Helper function to create a user with permissions.
 * 
 * This pattern is STANDARD for non-CRUD modules that require auth + permissions.
 */
function createUserWithUserPageAccess(array $permissionNames = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    if (!empty($permissionNames)) {
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => ucwords(str_replace('.', ' ', $name))]
            )->id;
        }
        $employee->permissions()->sync($permissions);
    }

    return $user;
}

describe('Get User By Employee API (unauthenticated)', function () {
    test('unauthenticated user cannot access user API', function () {
        $employee = Employee::factory()->create();
        $response = getJson("/api/employees/{$employee->id}/user");
        $response->assertUnauthorized();
    });
});

describe('Get User By Employee API', function () {
    beforeEach(function () {
        $user = createUserWithUserPageAccess(['user']);
        Sanctum::actingAs($user);
    });

    test('returns null user when employee has no linked user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = getJson("/api/employees/{$employee->id}/user");

        $response->assertOk()
            ->assertJson([
                'user' => null,
                'employee' => [
                    'name' => $employee->name,
                    'email' => $employee->email,
                ],
            ]);
    });

    test('returns user data when employee has linked user', function () {
        $linkedUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $linkedUser->id]);

        $response = getJson("/api/employees/{$employee->id}/user");

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $linkedUser->id,
                    'name' => $linkedUser->name,
                    'email' => $linkedUser->email,
                ],
            ]);
    });
});

describe('Update User API', function () {
    beforeEach(function () {
        $user = createUserWithUserPageAccess(['user']);
        Sanctum::actingAs($user);
    });

    test('creates new user for employee without linked user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User updated successfully.',
                'user' => [
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                ],
            ]);

        assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $employee->refresh();
        expect($employee->user_id)->not->toBeNull();
    });

    test('validates unique email constraint', function () {
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});
```

**Key Points:**
- ✅ Helper function untuk setup user + employee + permissions
- ✅ `beforeEach()` untuk reusable setup
- ✅ Describe blocks untuk organizing tests
- ✅ Test both success and validation scenarios

### Pattern B: Matrix/Bulk Tests (Permission Module)

```php
<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('permissions');

function createUserWithPermissionPageAccess(array $permissionNames = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    if (!empty($permissionNames)) {
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => ucwords(str_replace('.', ' ', $name))]
            )->id;
        }
        $employee->permissions()->sync($permissions);
    }

    return $user;
}

describe('Permission API Access', function () {
    test('authenticated user with permission can access permissions API', function () {
        $user = createUserWithPermissionPageAccess(['permission']);
        Sanctum::actingAs($user);

        $response = getJson('/api/permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'permissions' => [['id', 'name', 'display_name']],
            ]);
    });

    test('permissions API returns all permissions ordered by id', function () {
        Permission::factory()->create(['name' => 'test.permission.1']);
        Permission::factory()->create(['name' => 'test.permission.2']);

        $user = createUserWithPermissionPageAccess(['permission']);
        Sanctum::actingAs($user);

        $response = getJson('/api/permissions');

        $response->assertOk();
        $permissions = $response->json('permissions');
        expect($permissions)->toBeArray();

        foreach ($permissions as $permission) {
            expect($permission)->toHaveKeys(['id', 'name', 'display_name']);
        }
    });
});
```

**Key Points:**
- ✅ Similar helper pattern dengan Pattern A
- ✅ Test API endpoint (JSON assertions, bukan Inertia assertions)
- ✅ Validate data structure

---

## 📝 Naming Conventions

### Variable & Class Naming

| Context | Pattern | Example (User) | Example (Permission) |
|---------|---------|----------------|----------------------|
| Controller Class | PascalCase | `UserController` | `PermissionController` |
| Action Class | PascalCase, Verb+Entity+Parent | `SyncUserForEmployeeAction` | - |
| Request Class | PascalCase | `UpdateUserRequest` | - |
| Component | PascalCase | `UserForm` | `PermissionManager` |

### File & Directory Naming

| Type | Pattern | Example (User) | Example (Permission) |
|------|---------|----------------|----------------------|
| Route File | kebab-case (di `routes/api/`) | `users.php` | `permissions.php` |
| Component File | PascalCase | `UserForm.tsx` | `EmployeeSelector.tsx` |
| Hook File | camelCase | `useUserManagement.ts` | `usePermissionManagement.ts` |

### Route & API Naming

| Type | Pattern | Example (User) | Example (Permission) |
|------|---------|----------------|----------------------|
| Frontend Route | kebab-case | `/users` | `/permissions` |
| API Nested Route | kebab-case | `/api/employees/{employee}/user` | `/api/employees/{employee}/permissions` |
| Route Parameter | camelCase | `{employee}` | `{employee}` |
| Permission Name | snake_case | `user` | `permission` |

---

## 🚀 Langkah Implementasi

### Phase 1: Planning & Design

1. **Identifikasi Pattern**
   - Tentukan Pattern A (parent-child) atau Pattern B (matrix/bulk)
   - List operasi yang dibutuhkan
   - Identifikasi parent dan child entities

2. **Design Endpoints**
   ```
   Pattern A (User):
   - GET  /api/{parents}/{parent}/{feature}  (fetch)
   - POST /api/{parents}/{parent}/{feature}  (sync)
   
   Pattern B (Permission):
   - GET  /{features}  (page dengan bundled data)
   - GET  /api/{parents}/{parent}/{features}  (fetch)
   - POST /api/{parents}/{parent}/{features}  (sync)
   ```

3. **Baca Referensi**
   ```bash
   # WAJIB baca file referensi sebelum coding
   mcp_filesystem_read_file("app/Http/Controllers/UserController.php")
   mcp_filesystem_read_file("app/Http/Controllers/PermissionController.php")
   mcp_filesystem_read_file("tests/Feature/UserControllerTest.php")
   ```

### Phase 2: Backend Implementation

#### Pattern A: Parent-Child

1. **Action** (jika ada business logic kompleks)
   ```bash
   // Create action class
   app/Actions/{Features}/Sync{Feature}For{Parent}Action.php
   ```
   - Use DB::transaction
   - Handle create vs update logic
   - Return the entity

2. **Request** (conditional validation)
   ```bash
   app/Http/Requests/{Features}/Update{Feature}Request.php
   ```
   - Get parent dari route
   - Check existing child
   - Conditional rules

3. **Controller**
   ```bash
   app/Http/Controllers/{Feature}Controller.php
   ```
   - index() untuk page render
   - get{Feature}By{Parent}() untuk fetch
   - update{Feature}() untuk sync

4. **Routes**
   ```bash
   routes/api/{feature}.php
   ```
   - API-only routes (frontend via app-routes.tsx)

5. **Resource** (if needed)
   ```bash
   app/Http/Resources/{Features}/{Feature}Resource.php
   ```

#### Pattern B: Matrix/Bulk

1. **Controller**
   ```bash
   app/Http/Controllers/{Feature}Controller.php
   ```
   - index() returns JSON data
   - Use parent controller untuk sync operations

2. **Routes**
   ```bash
   routes/api/{feature}.php
   ```
   - API-only routes
   - API routes di parent controller

### Phase 3: Frontend Implementation

#### Pattern A: Parent-Child

1. **Create Custom Hook**
   ```bash
   resources/js/hooks/use{Feature}Management.ts
   ```
   - Form state
   - Fetch functions
   - Save handlers
   - Error handling

2. **Create Form Component**
   ```bash
   resources/js/components/{features}/{Feature}Form.tsx
   ```
   - Reusable form
   - Conditional UI
   - Error display

3. **Create Main Page**
   ```bash
   resources/js/pages/{features}/index.tsx
   ```
   - Full implementation
   - Use custom hook
   - FormProvider wrapper

#### Pattern B: Matrix/Bulk

1. **Create Components**
   ```bash
   resources/js/components/{features}/{Parent}Selector.tsx
   resources/js/components/{features}/{Feature}Manager.tsx
   ```

2. **Create Main Page**
   ```bash
   resources/js/pages/{features}/index.tsx
   ```
   - Fetch data via React Query
   - Watch parent selection
   - Conditional rendering

### Phase 4: Testing

1. **Feature Tests**
   ```bash
   tests/Feature/{Feature}ControllerTest.php
   ```
   - Helper function untuk auth + permissions
   - Describe blocks untuk organization
   - Test page access, API endpoints, validation

2. **Unit Tests** (jika ada Request custom)
   ```bash
   tests/Unit/Requests/{Features}/Update{Feature}RequestTest.php
   ```

### Phase 5: Verification

```bash
// turbo-all
./vendor/bin/sail test --filter={Feature}
```

Gunakan `mcp_laravel-boost_list-routes` untuk verify routes terdaftar.

---

## ✅ Checklist Sebelum PR

### Backend - Pattern A (Parent-Child)

- [ ] Action class dengan DB::transaction
- [ ] Action handle create dan update
- [ ] Request dengan conditional validation
- [ ] Controller dengan 3 methods (index, get, update)
- [ ] Routes dengan nested API endpoints
- [ ] Resource untuk response formatting

### Backend - Pattern B (Matrix/Bulk)

- [ ] Controller index() returns JSON data
- [ ] Sync operations di parent controller
- [ ] Routes dengan permission middleware

### Frontend - Both Patterns

- [ ] Custom hook untuk state management
- [ ] Reusable components
- [ ] Error handling
- [ ] Loading states
- [ ] Conditional UI

### Testing

- [ ] Helper function untuk auth + permissions setup
- [ ] Pest describe/test blocks
- [ ] Test page access
- [ ] Test API endpoints
- [ ] Test validation scenarios
- [ ] All tests pass

### Routes & Permissions

- [ ] Frontend route registered
- [ ] API routes registered dengan nested pattern
- [ ] Permission middleware configured
- [ ] Routes verified dengan `list-routes`

---

## 📋 Template Placeholders

```
Pattern A (Parent-Child):
├── {Parent}      → Employee, Customer, Organization
├── {Feature}     → User, Contact, Setting
├── {feature}     → user, contact, setting
└── {features}    → users, contacts, settings

Pattern B (Matrix/Bulk):
├── {Parent}      → Employee, User, Product
├── {Feature}     → Permission, Role, Tag
├── {feature}     → permission, role, tag
└── {features}    → permissions, roles, tags
```

---

## 💡 Tips & Best Practices

### Backend

1. **Use Actions for Complex Logic**
   - Don't put business logic in controllers
   - Use DB::transaction for multi-step operations
   - Return entities, not arrays

2. **Conditional Validation**
   - Check existing relationships in Request
   - Use Rule::unique()->ignore() for updates
   - Make fields conditional (required vs nullable)

3. **Nested Routes**
   - Always use parent route parameter
   - Validate parent exists (via route model binding)
   - Return parent context dengan child data

### Frontend

1. **Custom Hooks**
   - Encapsulate all state management
   - Separate concerns (fetch/save/error)
   - Return only necessary values

2. **Component Composition**
   - Make small, reusable components
   - Use controlled components
   - Props for configuration, not hardcoding

3. **Error Handling**
   - Show field-specific errors
   - Handle loading states
   - Provide user feedback

### Testing

1. **Helper Functions**
   - Create once, reuse everywhere
   - Setup auth + permissions in helper
   - Return User for actingAs()

2. **Organization**
   - Use describe blocks for grouping
   - beforeEach for common setup
   - Descriptive test names

3. **Coverage**
   - Test success paths
   - Test validation failures
   - Test permission checks

---

## 🔍 Troubleshooting

### Common Issues

**Issue**: Validation failing on update  
**Solution**: Ensure `Rule::unique()->ignore()` is used correctly

**Issue**: Permission middleware not working  
**Solution**: Check helper creates permissions with correct names

**Issue**: Frontend not receiving data  
**Solution**: Verify API endpoint returns JSON correctly, check React Query config

**Issue**: Nested route not found  
**Solution**: Check route parameter name matches (camelCase)

---

## 📚 Additional Resources

### MCP Tool Examples

```bash
# Check database relationships
mcp_laravel-boost_database-schema()

# Test relationships in tinker
mcp_laravel-boost_tinker("
  \$employee = App\\Models\\Employee::first();
  \$employee->user;
  \$employee->permissions;
")

# Find UI components
mcp_shadcn-ui-mcp-server_list_components()
mcp_shadcn-ui-mcp-server_get_component("combobox")
```

### Reference Files

**Always read before implementation:**
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/PermissionController.php`
- `tests/Feature/UserControllerTest.php`
- `resources/js/pages/users/index.tsx`
- `resources/js/pages/permissions/index.tsx`
