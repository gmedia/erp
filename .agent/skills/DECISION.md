# Agent Skills Decision Matrix

Gunakan tabel ini untuk memilih skill yang tepat berdasarkan kebutuhan task.

---

## 🎯 Quick Decision Tree

```
Apa yang ingin dilakukan?
│
├── Buat fitur baru
│   ├── CRUD 1 tabel, tanpa foreign key? ──────────→ /feature-crud-simple
│   ├── CRUD dengan relasi, filter kompleks? ─────→ /feature-crud-complex
│   └── Bukan CRUD (dashboard, settings, dll)? ───→ /feature-non-crud
│
├── Refactor kode existing
│   ├── Backend (Laravel/PHP)? ───────────────────→ /refactor-backend
│   └── Frontend (React/TypeScript)? ─────────────→ /refactor-frontend
│
└── Testing
    └── Buat test untuk fitur? ───────────────────→ /testing-strategy
```

---

## 📊 Decision Matrix

| Kondisi / Kebutuhan | Skill | Quick Command |
|---------------------|-------|---------------|
| CRUD 1 tabel, field sederhana | `feature-crud-simple` | `scaffold.sh Category` |
| CRUD dengan FK, filter range/date | `feature-crud-complex` | `scaffold.sh Product` |
| Dashboard, Settings, Matrix view | `feature-non-crud` | - |
| Perbaiki arsitektur backend | `refactor-backend` | `check-architecture.sh Employee` |
| Perbaiki struktur komponen frontend | `refactor-frontend` | - |
| Buat unit/feature/e2e test | `testing-strategy` | - |

---

## 🔍 Kriteria Detail

### feature-crud-simple
- ✅ Hanya 1 tabel utama
- ✅ Tidak ada relasi foreign key
- ✅ Filter hanya search text
- ✅ Form sederhana (< 5 field)

### feature-crud-complex
- ✅ Ada relasi belongsTo ke tabel lain
- ✅ Filter: dropdown, range (salary, date)
- ✅ Butuh komponen React terpisah (Form, Filters, Columns)
- ✅ Mungkin butuh DTO

### feature-non-crud
- ✅ Tidak ada model/resource baru
- ✅ Bekerja dengan existing models
- ✅ Custom UI (matrix, dashboard, wizard)
- ✅ Routing tidak standar

### refactor-backend
- ✅ Merapikan struktur Controller/Action/Domain
- ✅ Menambah FormRequest/Resource
- ✅ TIDAK mengubah API contract

### refactor-frontend
- ✅ Merapikan struktur komponen
- ✅ Extract logic ke hooks
- ✅ TIDAK mengubah data-testid

---

## 📁 Skill Locations

```
.agent/skills/
├── feature-crud-simple/     # Simple CRUD
├── feature-crud-complex/    # Complex CRUD with relations
├── feature-non-crud/        # Non-CRUD pages
├── refactor-backend/        # Backend refactoring
├── refactor-frontend/       # Frontend refactoring
└── testing-strategy/        # Testing guidelines
```

---

## 🚀 Cara Menggunakan

1. **Identifikasi kebutuhan** dari request user
2. **Pilih skill** berdasarkan decision tree di atas
3. **Baca SKILL.md** untuk panduan lengkap: `view_file .agent/skills/<skill-name>/SKILL.md`
4. **Jalankan script** jika tersedia (dengan `--help` dulu)
5. **Gunakan templates** dari folder `resources/`
