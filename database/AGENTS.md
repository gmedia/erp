# database/ — Schema, Factories & Seeders

## OVERVIEW

143 migrations, 96 factories, 37 seeders. Migrations are append-only after merge; factories mirror models 1:1; seeders are idempotent.

## STRUCTURE

```
database/
├── migrations/   # 143 timestamped, anonymous-class migrations
├── factories/    # 96 model factories (`@extends Factory<Model>`)
└── seeders/      # 37 seeders (base data + *SampleDataSeeder)
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

### Seeder Data Rules (MANDATORY)

- **Realistic data** — every user-visible value must look like real operational data. Never put the words `sample`, `dummy`, `test`, `contoh`, or `lorem` in loan/asset/person names, codes, purposes, notes, plate numbers, or license numbers.
- **No visible marker prefixes** — do not encode a demo marker in `code` (e.g. `VL-SAMPLE-...`) or in master records (`Sample Driver`, `B-SAMPLE-1`). Use production-like formats (e.g. `VL-2026-0001`, `B 1234 XYZ`, `Budi Santoso`).
- **Cover every stage** — sample seeders for workflow modules must create at least one row per status/state, including edge states (rejected, cancelled, overdue) when the module supports them.
- **Consistent source of truth** — when a module mirrors a workflow table, seed both sides consistently (e.g. `approval_requests` + `approval_request_steps` must match the mirrored `status`).
- **Idempotent + legacy cleanup** — use `firstOrCreate`/`updateOrCreate`; when renaming demo codes, purge the old demo rows inside the seeder so re-running does not leave duplicates.
- **No brand or tenant hardcode** — never hardcode company name, domain, logo, or email; resolve from `Setting`/`config`.
- **Verify** — run `migrate:fresh --seed` and the seeder a second time; counts must be identical, and spot-check each stage in the UI.

## ANTI-PATTERNS

- ❌ Editing a migration that already ran on a shared environment — add a new migration.
- ❌ Non-idempotent seeders (`create()` in loops).
- ❌ Seeder values that expose demo markers (`Sample: ...`, `Sample Driver`, `VL-SAMPLE-*`, `Dummy`) in any user-visible field.
- ❌ Seeder that only covers the happy path and leaves rejected/cancelled/overdue stages empty.
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
