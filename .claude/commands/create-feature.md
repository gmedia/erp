---
description: Membuat fitur CRUD baru (simple atau complex)
---

# Workflow: Buat Fitur CRUD

## 1. Tentukan Tipe CRUD

Analisa requirement user:
- **Simple CRUD**: 1 tabel, tanpa relasi FK, filter search saja
- **Complex CRUD**: Ada relasi, filter dropdown/range/date

## 2. Baca Skill yang Sesuai

Untuk Simple:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/feature-crud-simple/SKILL.md", startLine: 1, endLine: 250)
```

Untuk Complex:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/feature-crud-complex/SKILL.md", startLine: 1, endLine: 250)
```

Jika implementasi dipicu library/framework tertentu di luar Laravel ecosystem, resolve docs dulu via Context7 sebelum coding.

## 3. Jalankan Scaffold Script

Simple:
```bash
bash .github/skills/feature-crud-simple/scripts/scaffold.sh {FeatureName} --dry-run
```

Complex:
```bash
bash .github/skills/feature-crud-complex/scripts/scaffold.sh {FeatureName} --dry-run
```

## 4. Create Scaffold (setelah konfirmasi)

```bash
bash .github/skills/feature-crud-simple/scripts/scaffold.sh {FeatureName}
```

## 5. Implementasi Mengikuti Skill

Ikuti langkah di SKILL.md:
1. Model & Migration
2. Requests & Resources
3. Actions & Domain
4. Controller & Routes
5. Frontend
6. Tests

Catatan penting:
- Untuk wrapper request/resource yang sengaja kosong, gunakan body multiline dengan komentar intent seperti `// Intentionally empty. Behavior is inherited from the base class.` agar `./vendor/bin/sail bin duster fix` tidak mengompaknya menjadi one-line class.
- Untuk executable PHP code, import dependency di header file dan gunakan short class name. Hindari FQCN seperti `\App\...`, `\Illuminate\...`, `\Laravel\...`, atau `\Carbon\...` di body method/test.

## 6. Verifikasi

```bash
./vendor/bin/sail bin duster fix
```

```bash
./vendor/bin/sail test --filter={Feature}
```

## 7. E2E Test (Optional)

```bash
./vendor/bin/sail npm run test:e2e -- --grep={feature}
```
