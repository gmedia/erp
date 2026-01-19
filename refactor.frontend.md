# Refactor Frontend - Dokumentasi Perubahan

## Ringkasan

Refactoring frontend untuk mendukung perubahan backend yang mengubah `department` dan `position` dari string menjadi foreign key (`department_id` dan `position_id`).

---

## File yang Dimodifikasi (3 files)

| File                                                 | Perubahan                                             |
| ---------------------------------------------------- | ----------------------------------------------------- |
| `resources/js/types/employee.ts`                     | Update tipe id dari `string` ke `number`              |
| `resources/js/utils/schemas.ts`                      | Gunakan `z.coerce.string()` untuk department/position |
| `resources/js/components/employees/EmployeeForm.tsx` | Konversi numeric ID ke string dengan `String()`       |

---

## Detail Perubahan

### 1. employee.ts - Tipe Definisi

```diff
-    department: { id: string; name: string } | string;
-    position: { id: string; name: string } | string;
+    department: { id: number; name: string } | string;
+    position: { id: number; name: string } | string;
```

**Alasan:** Backend sekarang mengembalikan `id` sebagai integer (foreign key), bukan string.

---

### 2. schemas.ts - Form Validation Schema

```diff
-    department: z.string().min(1, { message: 'Department is required.' }),
-    position: z.string().min(1, { message: 'Position is required.' }),
+    department: z.coerce.string().min(1, { message: 'Department is required.' }),
+    position: z.coerce.string().min(1, { message: 'Position is required.' }),
```

**Alasan:** `z.coerce.string()` memungkinkan nilai numeric dari backend dikonversi ke string untuk form handling, menghilangkan error "Invalid input: expected string, received number".

---

### 3. EmployeeForm.tsx - Form Default Values

```diff
         department:
             typeof employee.department === 'object'
-                ? employee.department.id
+                ? String(employee.department.id)
                 : employee.department,
         position:
             typeof employee.position === 'object'
-                ? employee.position.id
+                ? String(employee.position.id)
                 : employee.position,
```

**Alasan:** Memastikan numeric ID dikonversi ke string untuk nilai form field.

---

## Verifikasi

### E2E Tests

```bash
./vendor/bin/sail npm run test:e2e
```

**Hasil:** ✅ 20/20 tests passed

---

## Catatan

- Komponen `EmployeeViewModal.tsx` dan `EmployeeColumns.tsx` tidak memerlukan modifikasi karena sudah menggunakan conditional check `typeof val === 'object' ? val.name : val`
- Perubahan ini backward compatible - form dapat menerima baik string maupun number untuk department/position
