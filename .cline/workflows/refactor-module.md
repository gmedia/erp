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

```
// turbo
```
Backend:
```bash
cat .agent/skills/refactor-backend/SKILL.md
```

Frontend:
```bash
cat .agent/skills/refactor-frontend/SKILL.md
```

## 3. Check Architecture (Backend)

```
// turbo
```
```bash
bash .agent/skills/refactor-backend/scripts/check-architecture.sh {ModuleName}
```

## 4. Identifikasi Issues

Dari output check-architecture atau review manual:
- [ ] Controller terlalu gemuk?
- [ ] Validasi tidak di FormRequest?
- [ ] Response tidak pakai Resource?
- [ ] Business logic di Controller?

## 5. Refactor Step by Step

### PENTING: Jangan ubah sekaligus!

1. **Satu layer dulu** (misal: FormRequest)
2. **Test** setelah setiap perubahan
3. **Lanjut** ke layer berikutnya

## 6. Verifikasi Setiap Step

```
// turbo
```
```bash
./vendor/bin/sail test --filter={Module}
```

## 7. Final Check

Backend:
```
// turbo
```
```bash
bash .agent/skills/refactor-backend/scripts/check-architecture.sh {ModuleName}
```

E2E:
```bash
./vendor/bin/sail npm run test:e2e -- --grep={module}
```

## 8. Dokumentasi

Buat ringkasan perubahan:
- Layer yang di-refactor
- Alasan perubahan
- Layer yang TIDAK diubah (dan kenapa)
