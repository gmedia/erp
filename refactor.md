# Refactor: Database Schema

## Commit: c011819

**Author:** oppytut <12345678+oppytut@users.noreply.github.com>  
**Date:** Sun Jan 18 15:43:01 2026 +0700  
**Subject:** refactor: db

---

## Summary

Refactoring skema database untuk mendukung foreign key constraint antara tabel `employees` dengan tabel `departments` dan `positions`, serta menambahkan modul **Permission** baru.

---

## Changed Files (9 files, +208 -8)

| File                                                                 | Changes                                                                                    |
| -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `app/Models/Employee.php`                                            | Dimodifikasi untuk menghapus kolom string dan menggunakan foreign key                      |
| `app/Models/Permission.php`                                          | **[NEW]** Model Permission dengan relasi parent-child (self-referencing)                   |
| `database/factories/PermissionFactory.php`                           | **[NEW]** Factory untuk model Permission                                                   |
| `database/migrations/2025_09_22_092704_create_employees_table.php`   | Mengubah `department` dan `position` dari string menjadi `department_id` dan `position_id` |
| `database/migrations/2025_10_06_013651_create_positions_table.php`   | Menambahkan foreign key constraint untuk `position_id`                                     |
| `database/migrations/2025_10_06_013652_create_departments_table.php` | Menambahkan foreign key constraint untuk `department_id`                                   |
| `database/migrations/2026_01_16_061912_create_permissions_table.php` | **[NEW]** Migration untuk tabel permissions dengan self-referencing                        |
| `database/seeders/DatabaseSeeder.php`                                | Menambahkan `PermissionSeeder` ke dalam daftar seeder                                      |
| `database/seeders/PermissionSeeder.php`                              | **[NEW]** Seeder untuk data permissions dengan struktur hierarki                           |

---

## Detail Perubahan

### 1. Employees Table Migration

**Before:**

```php
$table->string('department');
$table->string('position');
```

**After:**

```php
$table->unsignedBigInteger('department_id')->nullable();
$table->unsignedBigInteger('position_id')->nullable();
```

### 2. Foreign Key Constraints

Foreign key ditambahkan pada migration `positions` dan `departments`:

```php
// positions migration
Schema::table('employees', function (Blueprint $table) {
    $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
});

// departments migration
Schema::table('employees', function (Blueprint $table) {
    $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
});
```

### 3. Permission Model (New)

```php
class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Permission::class);
    }
}
```

### 4. Permissions Table Structure

```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->timestamps();

    $table->foreign('parent_id')->references('id')->on('permissions')->nullOnDelete();
});
```

### 5. Permission Seeder

Struktur hierarki permission yang di-seed:

```
- department
  - department.create
  - department.edit
  - department.delete
- position
  - position.create
  - position.edit
  - position.delete
- employee
  - employee.create
  - employee.edit
  - employee.delete
```

---

## Impact

1. **Breaking Change:** Kolom `department` dan `position` pada tabel `employees` diganti dengan foreign key
2. **Data Integrity:** Foreign key constraints memastikan referential integrity
3. **Cascading:** `nullOnDelete()` digunakan sehingga jika parent dihapus, child akan menjadi null
4. **New Feature:** Sistem permissions dengan struktur hierarki siap digunakan
