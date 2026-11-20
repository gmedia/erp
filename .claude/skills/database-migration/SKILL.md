---
name: database-migration
description: Panduan membuat migration, seeder, dan factory sesuai standar Laravel.
---

# Database Migration Skill

Gunakan skill ini untuk membuat atau memodifikasi struktur database dengan aman.

## 🔧 Tools yang Digunakan

> **PENTING**: Selalu cek schema existing sebelum membuat perubahan!

| Tool | Fungsi |
|------|--------|
| `./vendor/bin/sail artisan db:table {table}` (ringkas: `db:show`) | Lihat schema existing (tabel, kolom, FK, index) |
| Source `vendor/` versi terpasang + docs resmi | Cari dokumentasi Laravel migrations |
| `./vendor/bin/sail artisan tinker --execute='...'` | Verifikasi sample data atau test code snippet |
| Baca file | Baca file referensi existing |

### Contoh Penggunaan

```
# Sebelum buat migration, lihat schema existing:
./vendor/bin/sail artisan db:show

# Cari dokumentasi jika ragu (source versi terpasang):
rg -n "function foreignId" vendor/laravel/framework/src/Illuminate/Database/Schema

# Verifikasi struktur tabel setelah migrate:
./vendor/bin/sail artisan db:table products
```

---

## 🎯 Decision Tree

```
Apa yang perlu dilakukan?
│
├── Buat tabel baru ──────────────→ Migration + Model + Factory
├── Tambah kolom ke tabel ────────→ Migration (alter table)
├── Ubah struktur kolom ──────────→ Migration (modify column)
├── Hapus kolom/tabel ────────────→ Migration (drop) ⚠️ DESTRUCTIVE
├── Buat data awal ───────────────→ Seeder
└── Buat test data ───────────────→ Factory
```

---

## 🚀 Quick Start

### Commands
```bash
// turbo-all

# Buat migration
./vendor/bin/sail artisan make:migration create_{features}_table

# Buat model dengan migration + factory
./vendor/bin/sail artisan make:model {Feature} -mf

# Run migrations
./vendor/bin/sail artisan migrate

# Rollback last migration
./vendor/bin/sail artisan migrate:rollback
```

---

## 📁 Struktur File & Referensi

| Tipe | Lokasi | Referensi Pattern |
|------|--------|-------------------|
| Migration | `database/migrations/` | Ikuti pola `*_create_employees_table.php` |
| Model | `app/Models/` | Ikuti pola `Employee.php` |
| Factory | `database/factories/` | Ikuti pola `EmployeeFactory.php` |
| Seeder | `database/seeders/` | Ikuti pola `EmployeeSeeder.php` |

> **TIP**: Baca file referensi existing, bukan template.

---

## 🔄 Migration Patterns

### Create Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->index('name');
});
```

### Add Column
```php
Schema::table('products', function (Blueprint $table) {
    $table->string('sku')->after('name')->nullable();
});
```

### Drop Column
```php
Schema::table('products', function (Blueprint $table) {
    $table->dropForeign(['category_id']); // Drop FK dulu
    $table->dropColumn('category_id');
});
```

---

## 🔗 Foreign Key Rules

| Action | Syntax |
|--------|--------|
| Cascade delete | `->cascadeOnDelete()` |
| Set null on delete | `->nullOnDelete()` |
| Restrict delete | `->restrictOnDelete()` |

---

## ⚠️ Aturan Penting

### DILARANG di Production:
- ❌ `migrate:fresh` (hapus semua tabel)
- ❌ Drop column tanpa backup

### Best Practices:
- ✅ Cek schema via `./vendor/bin/sail artisan db:table {table}` sebelum buat migration
- ✅ Test migration di local dulu
- ✅ Gunakan `nullable()` untuk kolom baru
- ✅ Test rollback: `migrate:rollback` lalu `migrate` lagi

---

## ✅ Verification

```bash
// turbo-all

# Run migrations
./vendor/bin/sail artisan migrate

# Check migration status
./vendor/bin/sail artisan migrate:status

# Test rollback
./vendor/bin/sail artisan migrate:rollback
./vendor/bin/sail artisan migrate
```

Setelah migrate, gunakan `./vendor/bin/sail artisan db:table {table}` untuk verifikasi struktur tabel.
