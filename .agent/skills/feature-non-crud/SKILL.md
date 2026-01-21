---
name: Feature Custom / Non-CRUD
description: Workflow untuk fitur non-standar seperti Dashboard, Settings, User Management, atau Halaman Khusus.
---

# Feature Custom / Non-CRUD

Gunakan skill ini untuk membuat halaman atau fitur yang tidak mengikuti pola CRUD standar.

## 1. Decision Tree: Kapan Non-CRUD?

```
Fitur ini NON-CRUD jika:
├── Tidak ada entity/model utama baru
├── Bekerja dengan model existing (e.g., User Management → Employee)
├── Custom UI yang tidak fit pola create/edit/delete
├── Dashboard, Report, Settings, Matrix views
└── Routing tidak pakai Route::resource
```

**Contoh**: `users` (manage User via Employee), `permissions` (matrix view), Dashboard

---

## 2. Jenis Non-CRUD Patterns

### Pattern A: Related Entity Management (contoh: `users`)
- Tidak ada model `User` CRUD standar
- Manage via parent entity (`Employee`)
- Custom routes: `employees/{employee}/user`

### Pattern B: Matrix/Permission View (contoh: `permissions`)
- Display many-to-many relations
- Bulk update operations
- Custom UI components

### Pattern C: Dashboard/Report
- Aggregation queries
- Charts and widgets
- Read-only atau minimal interaction

---

## 3. Quick Start

### Template Files
Gunakan template dari folder `resources/` sebagai referensi:
- [CustomController.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-non-crud/resources/CustomController.php.template)
- [CustomRoutes.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-non-crud/resources/CustomRoutes.php.template)

---

## 4. Struktur File (bervariasi per pattern)

| Layer | Path | Notes |
|-------|------|-------|
| Controller | `app/Http/Controllers/` | Custom methods, tidak pakai resource |
| Actions | `app/Actions/{Feature}/` | Business logic terpisah |
| Requests | `app/Http/Requests/{Feature}/` | Untuk operasi custom |
| Resources | `app/Http/Resources/{Feature}/` | Jika ada API response |
| Routes | `routes/{feature}.php` | Custom route definitions |
| Pages | `resources/js/pages/{feature}/` | UI pages |
| Components | `resources/js/components/{feature}/` | Reusable UI components |

---

## 5. Referensi Contoh

### Users Module (Pattern A)
- [UserController.php](file:///home/ariefn/project/erp/app/Http/Controllers/UserController.php) - Custom methods, no standard CRUD
- [routes/user.php](file:///home/ariefn/project/erp/routes/user.php) - Custom routes via Employee
- [SyncUserForEmployeeAction.php](file:///home/ariefn/project/erp/app/Actions/Users/SyncUserForEmployeeAction.php) - Business logic

### Permissions Module (Pattern B)
- [PermissionController.php](file:///home/ariefn/project/erp/app/Http/Controllers/PermissionController.php)
- [routes/permission.php](file:///home/ariefn/project/erp/routes/permission.php)

---

## 6. Langkah Implementasi

### Phase 1: Define Scope
1. Tentukan tipe pattern (A, B, atau C)
2. List operasi yang dibutuhkan (bukan CRUD standar)
3. Desain custom routes

### Phase 2: Backend
4. Buat Controller dengan custom methods
5. Buat Actions untuk business logic kompleks
6. Buat Requests untuk validasi
7. Definisikan routes custom

### Phase 3: Frontend
8. Buat halaman index/main page
9. Buat komponen custom sesuai kebutuhan

### Phase 4: Testing
10. Fokus pada **Smoke Testing** (halaman bisa dibuka)
11. Test interaksi kunci (filter, save, etc.)

---

## 7. Contoh Code Patterns

### Custom Controller (Pattern A: User Management)
```php
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/index');
    }

    public function getUserByEmployee(Employee $employee): JsonResponse
    {
        return response()->json([
            'user' => $employee->user ? new UserResource($employee->user) : null,
            'employee' => ['name' => $employee->name, 'email' => $employee->email],
        ]);
    }

    public function updateUser(UpdateUserRequest $request, Employee $employee): JsonResponse
    {
        $user = (new SyncUserForEmployeeAction())->execute($employee, $request->validated());
        return response()->json(['message' => 'User updated successfully.', 'user' => new UserResource($user)]);
    }
}
```

### Custom Routes (Non-Resource)
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users')->middleware('permission:user');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:user,true')->group(function () {
        Route::get('employees/{employee}/user', [UserController::class, 'getUserByEmployee']);
        Route::post('employees/{employee}/user', [UserController::class, 'updateUser']);
    });
});
```

---

## 8. Verification Checklist

- [ ] Halaman bisa diakses (Status 200)
- [ ] Custom operasi bekerja dengan benar
- [ ] Permission middleware berfungsi
- [ ] Smoke test + interaction tests pass
