---
name: Refactor Frontend
description: Panduan refactor khusus Frontend (React SPA)
---

# Refactor Frontend (React SPA)

Lakukan refactor kode frontend secara **TERKONTROL** tanpa merusak frontend behavior, E2E test, atau API compatibility.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `activate_shadcn_ui_code_retrieval` | Aktifkan referensi source komponen UI untuk pattern |
| `mcp_shadcn-ui_get_component` | Ambil source komponen UI untuk pattern |
| `mcp_shadcn-ui_get_component_demo` | Lihat contoh usage bila perlu |
| `activate_laravel_logging_and_debugging_tools()` | Aktifkan browser log jika frontend error |
| `read_file` | Baca file referensi |

---

## 🚫 ATURAN BEHAVIOR COMPATIBILITY

### DILARANG Mengubah:
- ❌ `data-testid` (digunakan E2E test)
- ❌ Struktur data dari API
- ❌ Kontrak event antar komponen
- ❌ Route/URL yang diakses

### Yang DIPERBOLEHKAN:
- ✅ Refactor internal component
- ✅ Penambahan/perbaikan TypeScript typing
- ✅ Pemindahan logic ke helper/hooks
- ✅ Reuse component

### Anti-Pattern (DILARANG!):
- ❌ **JANGAN** import dari `@inertiajs/react` (sudah dihapus dari project)
- ✅ Navigasi: gunakan `Link` / `useNavigate` dari `react-router-dom`
- ✅ Meta tags: gunakan `Helmet` dari import `react-helmet-async` yang sudah dialias ke `resources/js/lib/react-helmet-async.tsx`
- ✅ Data fetching: gunakan React Query hooks (`useCrudQuery`, dll)
- ✅ API calls: gunakan `axios` dari `@/lib/axios`

---

## 🏗️ ARSITEKTUR FRONTEND

### Component Hierarchy
| Tipe | Tanggung Jawab |
|------|---------------|
| Page | Entry point, state management level page |
| Container | Logic, API calls, pass data ke children |
| Presentational | Rendering UI, props-driven, stateless |

### Naming Conventions
| Jenis | Format | Contoh |
|-------|--------|--------|
| Component file | PascalCase | `DepartmentTable.tsx` |
| Utility file | camelCase | `formatDate.ts` |
| Folder | kebab-case | `departments/` |
| Event handler | `handle...` | `handleSubmit` |
| Props callback | `on...` | `onSubmit` |

---

## 📖 Referensi Pattern

```
# Baca file referensi, bukan template:
read_file(filePath: "/absolute/path/to/project/resources/js/pages/positions/index.tsx", startLine: 1, endLine: 220)
read_file(filePath: "/absolute/path/to/project/resources/js/components/employees/EmployeeForm.tsx", startLine: 1, endLine: 220)

# Aktifkan tool retrieval komponen shadcn bila perlu source/pattern:
activate_shadcn_ui_code_retrieval()

# Ambil source komponen:
mcp_shadcn-ui_get_component(componentName: "table")

# Ambil demo penggunaan komponen bila perlu:
mcp_shadcn-ui_get_component_demo(componentName: "table")
```

| Pattern | File Referensi |
|---------|---------------|
| Page | `resources/js/pages/positions/index.tsx` |
| Form Component | `resources/js/components/employees/EmployeeForm.tsx` |
| Filters | `resources/js/components/employees/EmployeeFilters.tsx` |

---

## 💻 ATURAN CODING

### TypeScript Rules
```typescript
// ✅ BENAR - explicit typing
interface DepartmentFormProps {
  department?: Department;
  onSubmit: (data: DepartmentFormData) => void;
  onCancel: () => void;
}

export function DepartmentForm({ department, onSubmit, onCancel }: DepartmentFormProps) {
  // ...
}
```

### data-testid Rules
- ❌ JANGAN ubah existing `data-testid`
- ❌ JANGAN hapus `data-testid`
- ✅ BOLEH tambah `data-testid` baru

### DataTable Column Consistency Rules

Saat refactor komponen Columns.tsx, pastikan konsistensi berikut:

1. **Select Column** — WAJIB gunakan `createSelectColumn()` dari `@/utils/columns.tsx`
   - ❌ JANGAN buat custom select/checkbox column
   - `createSelectColumn()` sengaja TIDAK punya header checkbox

2. **Actions Column** — WAJIB gunakan `createActionsColumn()` dari `@/utils/columns.tsx`
   - ❌ JANGAN buat custom icon buttons untuk actions
   - `createActionsColumn()` menggunakan dropdown menu pattern

3. **Sortable Columns** — Gunakan `createTextColumn()` (default sortable) atau `createSortingHeader()`
   - Jika kolom TIDAK boleh sortable: set `enableSorting: false`
   - Pastikan setiap kolom sortable di frontend JUGA didukung di backend (validation rules)

> **PENGARUH KE E2E TEST**: Konsistensi ini memungkinkan shared test factories bekerja untuk semua modul.
> Lihat skill `refactor-e2e` untuk detail standar E2E test.

---

## ⚠️ ANTI OVER-ENGINEERING

JANGAN:
- ❌ Abstraksi tidak perlu
- ❌ Component terlalu granular
- ❌ Menambah dependency baru tanpa alasan
- ❌ Re-add dependency upstream `react-helmet-async` tanpa verifikasi kompatibilitas React 19

---

## ✅ Verification

```bash
// turbo-all
./vendor/bin/sail npm run test:e2e
```

Jika frontend error:
```
activate_laravel_logging_and_debugging_tools()
# lalu gunakan tool browser log Laravel yang tersedia
```

---

## 📋 CHECKLIST

- [ ] Single responsibility per component
- [ ] Proper props typing, no `any`
- [ ] Tidak ada perubahan `data-testid`
- [ ] E2E test PASS
