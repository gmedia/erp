---
description: Membuat fitur CRUD baru (simple atau complex)
---

# Workflow: Buat Fitur CRUD

## 1. Tentukan Tipe CRUD

Analisa requirement user:
- **Simple CRUD**: 1 tabel, tanpa relasi FK, filter search saja
- **Complex CRUD**: Ada relasi, filter dropdown/range/date

## 2. Baca Skill yang Sesuai

```
// turbo
```
Untuk Simple:
```bash
cat .agent/skills/feature-crud-simple/SKILL.md
```

Untuk Complex:
```bash
cat .agent/skills/feature-crud-complex/SKILL.md
```

## 3. Jalankan Scaffold Script

```
// turbo
```
Simple:
```bash
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh {FeatureName} --dry-run
```

Complex:
```bash
bash .agent/skills/feature-crud-complex/scripts/scaffold.sh {FeatureName} --dry-run
```

## 4. Create Scaffold (setelah konfirmasi)

```
// turbo
```
```bash
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh {FeatureName}
```

## 5. Implementasi Mengikuti Skill

Ikuti langkah di SKILL.md:
1. Model & Migration
2. Requests & Resources
3. Actions & Domain
4. Controller & Routes
5. Frontend
6. Tests

## 6. Verifikasi

```
// turbo
```
```bash
./vendor/bin/sail test --filter={Feature}
```

## 7. E2E Test (Optional)

```bash
./vendor/bin/sail npm run test:e2e -- --grep={feature}
```
