# database/ — Schema, Factories & Seeders

## OVERVIEW

106 migrations, 88 factories, 29 seeders. Migrations are append-only after merge; factories mirror models 1:1; seeders are idempotent.

## STRUCTURE

```
database/
├── migrations/   # 106 timestamped, anonymous-class migrations
├── factories/    # 88 model factories (`@extends Factory<Model>`)
└── seeders/      # 29 seeders (base data + *SampleDataSeeder)
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add table/column | `migrations/` — new timestamped file |
| Model factory | `factories/{Model}Factory.php` |
| Reference/base data | `seeders/{Module}Seeder.php` |
| Demo data | `seeders/{Module}SampleDataSeeder.php` |
| Model casts/fillable | `app/Models/` — see `app/AGENTS.md` |

## CONVENTIONS

- **Filename intent** — `YYYY_MM_DD_HHMMSS_{create\|add\|backfill\|drop}_*`. Suffix must describe the operation.
- **Anonymous migration class** — `return new class extends Migration` with typed `up(): void` / `down(): void`.
- **Foreign keys** — `foreignId('x_id')->constrained()->cascadeOnDelete()`; use `nullOnDelete()` for optional links. Add an explicit `$table->index(...)` for FK/filter columns.
- **Timestamps** — always `$table->timestamps()`; soft deletes via `$table->softDeletes()` when the model uses the trait.
- **Factories** — `definition(): array` returns relationships as `Model::factory()`, never hard-coded IDs. Keep the `@extends Factory<Model>` generic for PHPStan.
- **Idempotent seeders** — use `firstOrCreate` / `updateOrCreate`; re-running `db:seed` must not duplicate rows.
- **Naming** — sample/demo data seeders end in `SampleDataSeeder`.

## ANTI-PATTERNS

- ❌ Editing a migration that already ran on a shared environment — add a new migration.
- ❌ Non-idempotent seeders (`create()` in loops).
- ❌ Migrations without `down()` or with untyped closures.
- ❌ Missing indexes on foreign keys or frequently filtered columns.
- ❌ Factory states that hard-code IDs instead of `factory()` relations.

## COMMANDS

```bash
sail artisan migrate
sail artisan migrate:fresh --seed
sail artisan db:seed --class=BranchSeeder
sail artisan make:migration create_things_table
sail artisan make:factory ThingFactory --model=Thing
sail artisan make:seeder ThingSeeder
```
