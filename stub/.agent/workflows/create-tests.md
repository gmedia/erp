---
description: Membuat test untuk fitur existing
---

# Workflow: Buat Tests

## 1. Tentukan Scope Testing

- **Feature Test**: Test API endpoints (CRUD operations)
- **Unit Test**: Test business logic (Actions, Services)
- **E2E Test**: Test user journey (browser)

## 2. Baca Testing Strategy Skill

```
// turbo
```
```bash
cat .agent/skills/testing-strategy/SKILL.md
```

## 3. Identifikasi Test Gaps

Cek test yang sudah ada:
```bash
ls tests/Feature/*{Module}*
ls tests/Unit/Actions/{Module}/
ls tests/e2e/{module}/
```

## 4. Buat Feature Test (jika belum ada)

Gunakan template:
```
// turbo
```
```bash
cat .agent/skills/testing-strategy/resources/FeatureTest.php.template
```

Lokasi: `tests/Feature/{Module}ControllerTest.php`

## 5. Buat Unit Tests

Gunakan template:
```
// turbo
```
```bash
cat .agent/skills/testing-strategy/resources/UnitTest.php.template
```

Lokasi: `tests/Unit/Actions/{Module}/`

## 6. Buat E2E Tests

Gunakan template:
```
// turbo
```
```bash
cat .agent/skills/testing-strategy/resources/e2e.spec.ts.template
```

Lokasi: `tests/e2e/{module}/`

## 7. Run Tests

```
// turbo
```
```bash
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
