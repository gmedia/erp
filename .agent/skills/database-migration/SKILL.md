---
name: Database Migration
description: Panduan membuat migration, seeder, dan factory sesuai standar Laravel.
---

# Database Migration Skill

Gunakan skill ini untuk membuat atau memodifikasi struktur database dengan aman.

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
# Buat migration
./vendor/bin/sail artisan make:migration create_{features}_table

# Buat model dengan migration + factory
./vendor/bin/sail artisan make:model {Feature} -mf

# Run migrations
./vendor/bin/sail artisan migrate

# Rollback last migration
./vendor/bin/sail artisan migrate:rollback

# Fresh migrate (DESTRUCTIVE - recreate all)
./vendor/bin/sail artisan migrate:fresh --seed
```

### Template Files
- [create_table.php.template](file:///home/ariefn/project/erp/.agent/skills/database-migration/resources/create_table.php.template)
- [add_column.php.template](file:///home/ariefn/project/erp/.agent/skills/database-migration/resources/add_column.php.template)
- [factory.php.template](file:///home/ariefn/project/erp/.agent/skills/database-migration/resources/factory.php.template)
- [seeder.php.template](file:///home/ariefn/project/erp/.agent/skills/database-migration/resources/seeder.php.template)

---

## 📁 Struktur File

| Tipe | Lokasi | Contoh |
|------|--------|--------|
| Migration | `database/migrations/` | `2024_01_21_create_products_table.php` |
| Model | `app/Models/` | `Product.php` |
| Factory | `database/factories/` | `ProductFactory.php` |
| Seeder | `database/seeders/` | `ProductSeeder.php` |

---

## 🔄 Migration Patterns

### Create Table (Tabel Baru)

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->integer('stock')->default(0);
    $table->boolean('is_active')->default(true);
    
    // Foreign Keys
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users');
    
    $table->timestamps();
    $table->softDeletes(); // Jika perlu soft delete
    
    // Indexes
    $table->index('name');
    $table->unique(['name', 'category_id']);
});
```

### Add Column (Tambah Kolom)

```php
Schema::table('products', function (Blueprint $table) {
    $table->string('sku')->after('name')->nullable();
    $table->foreignId('brand_id')->after('category_id')->nullable()->constrained();
});
```

### Modify Column (Ubah Kolom)

```php
// Requires doctrine/dbal package
Schema::table('products', function (Blueprint $table) {
    $table->string('name', 500)->change(); // Ubah panjang
    $table->text('description')->nullable(false)->change(); // Ubah nullable
});
```

### Drop Column/Table

```php
// Drop column
Schema::table('products', function (Blueprint $table) {
    $table->dropColumn('old_column');
    $table->dropForeign(['category_id']); // Drop FK dulu
    $table->dropColumn('category_id');
});

// Drop table
Schema::dropIfExists('products');
```

---

## 🏭 Factory Pattern

```php
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90), // 90% true
            'category_id' => Category::factory(),
        ];
    }

    // State: Inactive product
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    // State: Out of stock
    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
```

---

## 🌱 Seeder Pattern

```php
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Option 1: Factory
        Product::factory()->count(50)->create();

        // Option 2: Specific data
        Product::create([
            'name' => 'Sample Product',
            'price' => 99.99,
            'category_id' => 1,
        ]);

        // Option 3: From array
        $products = [
            ['name' => 'Product A', 'price' => 10.00],
            ['name' => 'Product B', 'price' => 20.00],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
```

---

## ⚠️ Aturan Penting

### DILARANG di Production:
- ❌ `migrate:fresh` (hapus semua tabel)
- ❌ `migrate:reset` (rollback semua)
- ❌ Drop column tanpa backup
- ❌ Ubah tipe data yang bisa kehilangan data

### Best Practices:
- ✅ Selalu test migration di local dulu
- ✅ Buat migration kecil dan spesifik
- ✅ Gunakan `nullable()` untuk kolom baru di tabel existing
- ✅ Backup database sebelum migration production
- ✅ Test rollback: `migrate:rollback` lalu `migrate` lagi

---

## 🔗 Foreign Key Rules

| Action | Syntax |
|--------|--------|
| Cascade delete | `->cascadeOnDelete()` |
| Set null on delete | `->nullOnDelete()` |
| Restrict delete | `->restrictOnDelete()` |
| Cascade update | `->cascadeOnUpdate()` |

```php
// Contoh lengkap
$table->foreignId('user_id')
    ->constrained()
    ->cascadeOnDelete()
    ->cascadeOnUpdate();
```

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

# Run seeder
./vendor/bin/sail artisan db:seed --class=ProductSeeder
```

---

## 🛠️ Troubleshooting

| Error | Solusi |
|-------|--------|
| "Table already exists" | Rollback atau drop table manual |
| "Foreign key constraint fails" | Drop FK dulu, atau cascade delete |
| "Column not found" | Cek nama column, case-sensitive |
| "Cannot change column type" | Install `doctrine/dbal` package |
