---
name: Refactor Frontend
description: Panduan refactor khusus Frontend (Inertia/React)
---
# PANDUAN REFACTOR TERKONTROL - LARAVEL FRONTEND

## 🎯 TUJUAN UTAMA

Lakukan **ANALISA dan REFACTOR** kode frontend secara **TERKONTROL dan TERSTRUKTUR** pada modul berikut, **TANPA merusak**:

- Frontend behavior
- E2E test yang sudah ada
- Kompatibilitas dengan API backend existing

**Refactor HARUS fokus pada:**

- Keterbacaan kode
- Konsistensi struktur component
- Pemisahan tanggung jawab tanpa mengubah perilaku sistem

> **Prinsip Utama:** Refactor ≠ Rewrite

---

## 📦 SCOPE REFACTOR

### Frontend Modules

**Modul Departments:**

- `@/resources/js/pages/departments`
- `@/resources/js/components/departments`
- `@/tests/e2e/departments`

**Modul Positions:**

- `@/resources/js/pages/positions`
- `@/resources/js/components/positions`
- `@/tests/e2e/positions`

**Modul Employees:**

- `@/resources/js/pages/employees`
- `@/resources/js/components/employees`
- `@/tests/e2e/employees`

### Shared Code

- `@/resources/js/components/common`

---

## 🚫 ATURAN PALING PENTING: BEHAVIOR COMPATIBILITY

### DILARANG KERAS Mengubah:

- ❌ Struktur data yang diterima dari API
- ❌ `data-testid` (digunakan untuk e2e test)
- ❌ Kontrak event antar komponen
- ❌ Struktur payload API request
- ❌ Key response API
- ❌ Selector yang digunakan e2e test
- ❌ Route/URL yang diakses frontend

### Yang DIPERBOLEHKAN:

- ✅ Refactor internal component
- ✅ Perbaikan struktur kode
- ✅ Penambahan/perbaikan typing (TypeScript)
- ✅ Pemindahan logic ke helper/utility functions
- ✅ Optimisasi tanpa mengubah output/behavior
- ✅ Reuse component (mengurangi duplikasi)

### Kompatibilitas Wajib:

- Frontend HARUS tetap kompatibel dengan backend existing
- Response API yang dikonsumsi HARUS tetap sesuai kontrak

---

## 🏗️ ARSITEKTUR FRONTEND (WAJIB DIPATUHI)

### 1. Component Structure

**Prinsip Utama:**

- Component HARUS memiliki **single responsibility**
- Component HARUS **reusable** jika memungkinkan
- Pisahkan **presentational component** dari **container component**

**Struktur Folder:**

```
resources/js/
├── components/
│   ├── common/           # Shared/reusable components
│   ├── departments/      # Department-specific components
│   ├── positions/        # Position-specific components
│   └── employees/        # Employee-specific components
├── pages/
│   ├── departments/      # Department pages
│   ├── positions/        # Position pages
│   └── employees/        # Employee pages
├── hooks/                # Custom React hooks (jika ada)
├── utils/                # Utility functions
├── types/                # TypeScript type definitions
└── services/             # API service layer (jika ada)
```

---

### 2. Component Hierarchy

**Page Component:**

- Entry point untuk setiap halaman
- Menghandle state management level page
- Mengkoordinasikan child components

**Container Component:**

- Menghandle logic dan state
- Memanggil API services
- Meneruskan data ke presentational components

**Presentational Component:**

- Fokus pada rendering UI
- Menerima data via props
- Stateless jika memungkinkan

---

### 3. Naming Conventions

**File Naming:**

- PascalCase untuk component files: `DepartmentTable.tsx`
- camelCase untuk utility files: `formatDate.ts`
- kebab-case untuk folder: `departments/`

**Component Naming:**

- Prefix dengan nama modul jika spesifik: `DepartmentForm`, `EmployeeList`
- Suffix yang menjelaskan fungsi: `...Modal`, `...Table`, `...Form`, `...Card`

**Variable & Function Naming:**

- camelCase untuk variables dan functions
- UPPER_SNAKE_CASE untuk constants
- Prefix `handle` untuk event handlers: `handleSubmit`, `handleDelete`
- Prefix `on` untuk props callback: `onSubmit`, `onDelete`

---

### 4. TypeScript Rules

**WAJIB:**

- ✅ Explicit typing untuk props
- ✅ Interface untuk complex objects
- ✅ Type untuk API responses
- ✅ Avoid `any` type

**Contoh Props Typing:**

```typescript
interface DepartmentFormProps {
  department?: Department;
  onSubmit: (data: DepartmentFormData) => void;
  onCancel: () => void;
  isLoading?: boolean;
}

export function DepartmentForm({ 
  department, 
  onSubmit, 
  onCancel, 
  isLoading = false 
}: DepartmentFormProps) {
  // implementation
}
```

---

### 5. State Management

**Local State:**

- Gunakan `useState` untuk state yang hanya dibutuhkan component itu sendiri
- Gunakan `useReducer` untuk state kompleks

**Shared State:**

- Gunakan context atau state management library yang sudah ada
- JANGAN memperkenalkan library state management baru tanpa persetujuan

**Derived State:**

- Gunakan `useMemo` untuk computed values
- Hindari duplicated state

---

### 6. API Integration

**Aturan:**

- Gunakan service layer yang sudah ada
- JANGAN mengubah endpoint atau payload structure
- Handle loading dan error states secara konsisten

**Pattern:**

```typescript
// ✅ BENAR - menggunakan service layer
const { data, isLoading, error } = useDepartments();

// ❌ SALAH - direct fetch tanpa service
const data = await fetch('/api/departments');
```

---

### 7. Event Handling

**Naming:**

- Handler function: `handleEventName`
- Props callback: `onEventName`

**Contoh:**

```typescript
// Di parent component
<DepartmentForm onSubmit={handleCreateDepartment} />

// Di child component
function DepartmentForm({ onSubmit }: Props) {
  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };
}
```

---

### 8. 🚨 PRINSIP ANTI OVER-ENGINEERING (WAJIB)

**JANGAN:**

- ❌ Membuat abstraksi yang tidak perlu
- ❌ Membuat component terlalu granular
- ❌ Premature optimization
- ❌ Menambah dependency baru tanpa alasan kuat

**Dalam Kasus Sederhana:**

- Inline styles untuk one-off styling boleh
- Simple component tidak perlu dipecah lebih kecil
- Logic sederhana tidak perlu di-extract ke hook

**WAJIB:**

- Jika refactor sengaja TIDAK dilakukan, **jelaskan alasannya secara eksplisit**

---

## 💻 ATURAN CODING FRONTEND

### Component Best Practices

**Props:**

- Destructure props di function signature
- Gunakan default values jika applicable
- Document props dengan JSDoc jika kompleks

**JSX:**

- Satu return statement per component
- Conditional rendering yang jelas
- Hindari nested ternary

**Contoh:**

```typescript
// ✅ BENAR
export function DepartmentCard({ 
  department, 
  onEdit, 
  onDelete,
  isEditable = true 
}: DepartmentCardProps) {
  return (
    <Card>
      <CardHeader>{department.name}</CardHeader>
      <CardBody>{department.description}</CardBody>
      {isEditable && (
        <CardFooter>
          <Button onClick={onEdit}>Edit</Button>
          <Button onClick={onDelete}>Delete</Button>
        </CardFooter>
      )}
    </Card>
  );
}
```

---

### Data-Testid Rules

**JANGAN PERNAH:**

- ❌ Mengubah existing `data-testid`
- ❌ Menghapus `data-testid`
- ❌ Mengubah struktur element yang memiliki `data-testid`

**BOLEH:**

- ✅ Menambah `data-testid` baru jika diperlukan
- ✅ Refactor internal tanpa mengubah `data-testid`

---

### Import Organization

**Urutan Import:**

1. React and framework imports
2. Third-party libraries
3. Internal components
4. Hooks and utilities
5. Types
6. Styles

**Contoh:**

```typescript
// React & framework
import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';

// Third-party
import { toast } from 'sonner';

// Internal components
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/common/Modal';

// Hooks & utilities
import { useDepartments } from '@/hooks/useDepartments';
import { formatDate } from '@/utils/formatDate';

// Types
import type { Department } from '@/types/department';
```

---

### Error Handling

**UI Error States:**

- Tampilkan error message yang user-friendly
- Provide action untuk recovery jika memungkinkan
- Log error untuk debugging

**Pattern:**

```typescript
if (error) {
  return (
    <ErrorMessage 
      message="Failed to load departments" 
      onRetry={refetch} 
    />
  );
}
```

---

## 🧪 ATURAN TESTING & BUILD

### Command yang HARUS PASS:

```bash
./vendor/bin/sail npm run test:e2e
```

### Jika Test Gagal:

- ✅ WAJIB diperbaiki
- ❌ DILARANG menghapus test
- ❌ DILARANG menonaktifkan assertion
- ❌ DILARANG skip test

---

## ⚙️ ATURAN COMMAND TERMINAL

### SELALU Gunakan Sail:

```bash
./vendor/bin/sail <command>
```

### JANGAN Mengeksekusi:

```bash
./vendor/bin/sail npm run format
```

### WAJIB Urutan Build:

```bash
./vendor/bin/sail npm run build
./vendor/bin/sail npm run types
```

---

## 📋 OUTPUT YANG DIHARAPKAN

### 1. Kode Hasil Refactor

- Behavior tetap sama (no breaking changes)
- Struktur lebih rapi dan konsisten
- Component lebih reusable
- Lebih mudah di-maintain

### 2. Dokumentasi Singkat (Bullet Points)

Untuk setiap modul, jelaskan:

- **Component yang di-refactor:**
    - `DepartmentTable.tsx` - extracted pagination logic
    - `DepartmentForm.tsx` - improved typing

- **Alasan refactor:**
    - Contoh: "Extracted `useTablePagination` hook untuk reuse di module lain"
    - Contoh: "Memisahkan `DepartmentFormModal` dari `DepartmentForm` untuk clarity"

### 3. Component yang:

- Memiliki single responsibility
- Konsisten dengan naming convention
- Properly typed
- Mudah dibaca dan dipahami

---

## ✅ PRINSIP AKHIR

### Decision Making:

- **Jika ragu:** PERTAHANKAN implementasi lama
- **Jangan mengorbankan:** Stabilitas demi "kebersihan"
- **Fokus pada:** Kejelasan dan keberlanjutan jangka panjang

### Priority:

1. **Stabilitas** (no breaking changes)
2. **Konsistensi** (follow established patterns)
3. **Keterbacaan** (code clarity)
4. **Maintainability** (easy to extend)

### Red Flags (Tanda Bahaya):

- 🚩 E2E test yang gagal setelah refactor
- 🚩 UI behavior berubah setelah refactor
- 🚩 API call berubah struktur
- 🚩 Over-engineering (abstraksi yang tidak perlu)

---

## 📚 CHECKLIST REFACTOR

### Per Modul (Departments / Positions / Employees):

**Component Structure:**

- [ ] Single responsibility per component
- [ ] Consistent naming convention
- [ ] Proper props typing
- [ ] Clean import organization

**Code Quality:**

- [ ] No `any` types (minimal)
- [ ] Proper error handling
- [ ] Consistent event handler naming
- [ ] Extracted reusable logic ke hooks/utils

**Compatibility:**

- [ ] Tidak ada perubahan pada `data-testid`
- [ ] Tidak ada perubahan pada struktur data API
- [ ] Tidak ada perubahan pada UI behavior
- [ ] Event contracts tetap sama

**Testing:**

- [ ] `./vendor/bin/sail npm run test:e2e` PASS

**Documentation:**

- [ ] Penjelasan component yang di-refactor dengan alasan
- [ ] Ringkasan perubahan per modul

---

## 🎯 CONTOH FLOW REFACTOR

### Sebelum (Bad):

```typescript
// Component terlalu besar, mixing concerns
export function DepartmentPage() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingDepartment, setEditingDepartment] = useState(null);
  
  useEffect(() => {
    setLoading(true);
    fetch(`/api/departments?page=${page}&search=${search}`)
      .then(res => res.json())
      .then(data => {
        setData(data);
        setLoading(false);
      })
      .catch(err => {
        setError(err);
        setLoading(false);
      });
  }, [page, search]);
  
  // 200+ lines of mixed logic and JSX...
}
```

### Sesudah (Good):

```typescript
// Page component - orchestration only
export function DepartmentPage() {
  const { data, isLoading, error, refetch } = useDepartments();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingDepartment, setEditingDepartment] = useState<Department | null>(null);
  
  const handleEdit = (department: Department) => {
    setEditingDepartment(department);
    setIsModalOpen(true);
  };
  
  const handleModalClose = () => {
    setEditingDepartment(null);
    setIsModalOpen(false);
  };
  
  if (error) {
    return <ErrorMessage message="Failed to load departments" onRetry={refetch} />;
  }
  
  return (
    <PageLayout title="Departments">
      <DepartmentTable 
        data={data} 
        isLoading={isLoading}
        onEdit={handleEdit}
      />
      <DepartmentFormModal
        department={editingDepartment}
        isOpen={isModalOpen}
        onClose={handleModalClose}
        onSuccess={refetch}
      />
    </PageLayout>
  );
}
```

**Penjelasan:**

- Data fetching dipindah ke custom hook `useDepartments`
- Logic table dipindah ke `DepartmentTable` component
- Modal logic dipindah ke `DepartmentFormModal` component
- Page component hanya orchestration
- Proper typing untuk semua props dan state

---

**END OF DOCUMENT**

---

## 🛠️ MCP Tools Support (Shadcn UI)
Manfaatkan tools ini untuk UI yang konsisten:
1. **get_component**: Jangan copy-paste manual dari web. Gunakan tool ini untuk tarik code komponen akurat.
2. **get_block**: Gunakan untuk scaffold halaman dashboard/login jika diminta user (misal: "dashboard-01").
