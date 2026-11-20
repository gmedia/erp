---
description: Membuat test untuk fitur existing
---

# Workflow: Buat Tests

## 1. Tentukan Scope Testing

- **Feature Test**: Test API endpoints (CRUD operations)
- **Unit Test**: Test business logic (Actions, Services)
- **E2E Test**: Test user journey (browser)

## 2. Baca Testing Strategy Skill

```text
read_file(filePath: "/absolute/path/to/project/.github/skills/testing-strategy/SKILL.md", startLine: 1, endLine: 250)
```

## 3. Identifikasi Test Gaps

Cek test yang sudah ada:
```bash
ls tests/Feature/*{Module}*
ls tests/Unit/Actions/{Module}/
ls tests/e2e/{module}/
```

Jika test dipicu oleh behavior package/framework eksternal, gunakan Context7 untuk docs terbaru sebelum menulis assertion atau setup.

## 4. Buat Feature Test (jika belum ada)

Gunakan template:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/testing-strategy/resources/FeatureTest.php.template", startLine: 1, endLine: 250)
```

Lokasi: `tests/Feature/{Module}ControllerTest.php`

Catatan penting:
- Karena project ini API-only, feature test PHP wajib import `Laravel\Sanctum\Sanctum` lalu pakai `Sanctum::actingAs($user, ['*']);`.
- Import `Storage`, `Carbon`, `Rule`, dan model yang dipakai di header file. Hindari FQCN seperti `\Laravel\Sanctum\Sanctum` atau `\Illuminate\Support\Facades\Storage` di body test.

## 5. Buat Unit Tests

Gunakan template:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/testing-strategy/resources/UnitTest.php.template", startLine: 1, endLine: 250)
```

Lokasi: `tests/Unit/Actions/{Module}/`

## 6. Buat E2E Tests

Gunakan template:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/testing-strategy/resources/e2e.spec.ts.template", startLine: 1, endLine: 250)
```

Lokasi: `tests/e2e/{module}/`

## 7. Run Tests

```bash
./vendor/bin/sail bin duster fix

./vendor/bin/sail test --filter={Module}
```

E2E:
```bash
./vendor/bin/sail npm run test:e2e -- --grep={module}
```

## 8. Fix Failing Tests

Jika test gagal:
1. Baca error message
2. Fix issue (bukan disable test!)
3. Run ulang
