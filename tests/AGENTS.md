# tests/ — Testing Knowledge Base

## OVERVIEW

Three testing layers: Pest (Feature + Unit) for backend, Playwright for E2E browser tests. All use `->group('{module-slug}')` kebab-case.

## STRUCTURE

```
tests/
├── Feature/{ModuleName}/    # Controller tests, export tests, action tests
├── Unit/
│   ├── Models/              # Model relationship/cast/scope tests
│   ├── Actions/             # Action unit tests
│   ├── Domain/              # FilterService tests
│   ├── Requests/            # FormRequest validation tests
│   └── Resources/           # Resource transformation tests
├── e2e/{module-slug}/       # Playwright specs + helpers
├── Traits/                  # 10 shared test traits
└── Pest.php                 # Pest config (base TestCase, RefreshDatabase)
```

## CONVENTIONS

- **Auth in Feature tests** — `Sanctum::actingAs($user)` (NEVER `actingAs`)
- **Assertions** — `assertJson()`, `assertJsonStructure()`, `assertOk()` (NEVER `assertInertia`)
- **Group naming** — `->group('{module-slug}')` single kebab-case tag only
- **E2E auth** — Bearer token injected to localStorage
- **E2E structure** — `helpers.ts` (factories/utils) + `{module}.spec.ts`
- **E2E standard** — 9-10 test cases per module (Search, Filters, Add, View, Edit, Export, Checkbox, Sorting, Delete, Import)
- **Factories** — 67 factories, 1:1 with models
- **Run by module** — `sail test --group {slug}` or `npx playwright test tests/e2e/{slug}/`

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add feature test | `Feature/{ModuleName}/{Module}ControllerTest.php` |
| Add export test | `Feature/{ModuleName}/{Module}ExportTest.php` |
| Add model unit test | `Unit/Models/{Module}Test.php` |
| Add E2E test | `e2e/{module-slug}/{module}.spec.ts` |
| Shared test helpers | `Traits/` — 10 reusable traits |
| E2E config | `playwright.config.ts` (root) |
| Pest config | `Pest.php`, `phpunit.xml` |

## ANTI-PATTERNS

- ❌ `actingAs($user)` without Sanctum
- ❌ `assertInertia()` assertions
- ❌ Multiple group tags per test
- ❌ snake_case in group names (use kebab-case)
- ❌ E2E tests without `waitForResponse('/api/...')`
