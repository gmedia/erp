---
name: Database Migration
description: Panduan membuat migration, seeder, dan factory sesuai standar Laravel.
---

# Database Migration Skill

Gunakan skill ini untuk membuat atau memodifikasi struktur database dengan aman.

## 🔌 MCP Tools yang Digunakan

> **PENTING**: Selalu gunakan MCP tools ini sebelum membuat perubahan!

| Tool | Fungsi |
|------|--------|
| `mcp_laravel-boost_database-schema` | Lihat schema existing (tabel, kolom, FK, index) |
| `mcp_laravel-boost_database-query` | Query read-only untuk verifikasi data |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Laravel migrations |
| `mcp_laravel-boost_tinker` | Test code snippet |

### Contoh Penggunaan

```
# Sebelum buat migration, lihat schema existing:
mcp_laravel-boost_database-schema()

# Cari dokumentasi jika ragu:
mcp_laravel-boost_search-docs(queries: ["migration foreign key"])

# Verifikasi data setelah migrate:
mcp_laravel-boost_database-query(query: "SELECT * FROM products LIMIT 5")
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

> **TIP**: Gunakan `mcp_filesystem_read_file` untuk baca file referensi, bukan template.

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
- ✅ Gunakan `mcp_laravel-boost_database-schema` sebelum buat migration
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

Setelah migrate, gunakan `mcp_laravel-boost_database-query` untuk verifikasi struktur tabel.
