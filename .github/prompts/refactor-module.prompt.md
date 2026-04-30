---
description: Refactor modul existing (backend atau frontend)
---

# Workflow: Refactor Modul

## 1. Tentukan Scope

Analisa request user:
- **Backend only**: Controller, Actions, Requests, Resources
- **Frontend only**: Components, hooks, types
- **Full-stack**: Keduanya (HATI-HATI dengan API contract)

## 2. Baca Skill yang Sesuai

Backend:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/refactor-backend/SKILL.md", startLine: 1, endLine: 250)
```

Frontend:
```text
read_file(filePath: "/absolute/path/to/project/.github/skills/refactor-frontend/SKILL.md", startLine: 1, endLine: 250)
```

## 3. Map Blast Radius dan Dependency

Sebelum edit:
- Untuk file target, ambil konteks dengan `mcp_depwire_get_file_context(filePath: "app/Services/ExampleService.php")`
- Untuk symbol yang mungkin diubah, jalankan `mcp_depwire_impact_analysis(symbol: "ExampleService", file: "app/Services/ExampleService.php")`
- Jika akan rename/move/delete/split/merge file, jalankan `mcp_depwire_simulate_change(...)` lebih dulu
- Jika refactor dipicu perubahan package/framework/API, resolve docs via Context7 sebelum edit

## 4. Check Architecture (Backend)

```bash
bash .github/skills/refactor-backend/scripts/check-architecture.sh {ModuleName}
```

## 5. Identifikasi Issues

Dari output Depwire, check-architecture, atau review manual:
- [ ] Controller terlalu gemuk?
- [ ] Validasi tidak di FormRequest?
- [ ] Response tidak pakai Resource?
- [ ] Business logic di Controller?

## 6. Refactor Step by Step

### PENTING: Jangan ubah sekaligus!

1. **Satu layer dulu** (misal: FormRequest)
2. **Test** setelah setiap perubahan
3. **Lanjut** ke layer berikutnya

Catatan penting:
- Jika menemukan wrapper request/resource kosong yang hanya mewarisi behavior dari base class, pertahankan body multiline dan tambahkan komentar intent seperti `// Intentionally empty. Behavior is inherited from the base class.` sebelum menjalankan formatter.
- Untuk executable PHP code, pindahkan dependency ke import di bagian atas file. Hindari FQCN seperti `\App\...`, `\Illuminate\...`, `\Laravel\...`, atau `\Carbon\...` di body code kecuali untuk PHPDoc atau `::class` metadata.

## 7. Verifikasi Setiap Step

```bash
./vendor/bin/sail test --filter={Module}
```

## 8. Final Check

Backend:
```bash
./vendor/bin/sail bin duster fix
```

```bash
bash .github/skills/refactor-backend/scripts/check-architecture.sh {ModuleName}
```

E2E:
```bash
./vendor/bin/sail npm run test:e2e -- --grep={module}
```

## 9. Dokumentasi

Buat ringkasan perubahan:
- Layer yang di-refactor
- Alasan perubahan
- Layer yang TIDAK diubah (dan kenapa)
