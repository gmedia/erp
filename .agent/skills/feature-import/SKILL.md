---
name: Feature Import Excel
description: Workflow untuk menambahkan fitur import data melalui file Excel pada modul CRUD existing.
---

# Feature Import Excel

Skill ini digunakan untuk menambahkan fitur **Import** (biasanya via `.xlsx`, `.xls`, `.csv`) pada modul CRUD yang sudah ada. Arsitektur fitur import ini mengadopsi pola yang digunakan pada modul `Employee` dan `Supplier`.

## 1. Persiapan & Analisis

Sebelum membuat file, pelajari modul target:
1. Pahami struktur tabel dan kolom mandatory.
2. Identifikasi _Foreign Key_ (FK) referensi yang diperlukan. Misalnya, field `department_id` mereferensikan tabel `departments`. Di excel, user akan mengisi _Nama Department_, bukan ID.
3. Identifikasi _Unique Key_ untuk kebutuhan _Upsert_ (Insert atau Update). Misalnya email pada _Employee_, email/nama pada _Supplier_, atau kode pada _Asset_.

## 2. Struktur Backend

Fitur import membutuhkan 4 komponen utama di backend:

### A. Importer Class (`app/Imports/{Module}Import.php`)

Gunakan library `Maatwebsite\Excel`.
- Harus meng-implement: `ToCollection`, `WithHeadingRow`, `SkipsEmptyRows`.
- Class ini bertugas memvalidasi, memetakan FK, dan melakukan upsert baris per baris.
- **Penting:** _Load_ semua kemungkinan FK lookup di `__construct()` menggunakan `pluck('id', 'name')` untuk mencegah query `N+1` di dalam looping import.
- Format laporan return: simpan jumlah `importedCount`, `skippedCount`, dan detail `errors` (array assoc dengan keys `row`, `field`, `message`).

### B. Action Class (`app/Actions/{Modules}/Import{Modules}Action.php`)

Class logika yang meng-orkestrasikan proses import.
- Menerima `UploadedFile`.
- Menjalankan `Excel::import(new {Module}Import, $file)`.
- Mengembalikan array associative summary (`imported`, `skipped`, `errors`) yang diambil dari class Importer.

### C. Form Request (`app/Http/Requests/{Modules}/Import{Module}Request.php`)

Memvalidasi file upload.
- Rules standar: `'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240'` (Max 10MB).

### D. Controller & Route

1. Buka `{Module}Controller.php`. Tambahkan method:
   ```php
   public function import(Import{Module}Request $request): JsonResponse
   {
       $summary = (new \App\Actions\{Modules}\Import{Modules}Action)->execute($request->file('file'));
       return response()->json($summary);
   }
   ```
2. Buka `routes/{module}.php`. Tambahkan route `POST`:
   ```php
   Route::post('{slug}/import', [Controller::class, 'import'])
       ->name('{slug}.import')
       ->middleware('permission:{slug}.create,true');
   ```

## 3. Struktur Frontend

1. **Komponen Dialog:** Buat `Import{Module}Dialog.tsx` (atau re-use generic `ImportDialog.tsx` jika ada).
   - Dialog harus memiliki area _drag n drop_ atau _file input_.
   - Harus terdapat tombol **Download Template**. Template ini bisa sekadar file excel statis `.xlsx` kosong yang hanya berisi _Heading_ sesuai format _Import_, diletakkan pada folder `/public/templates/` (misal `/templates/import_{module}.xlsx`), atau di-generate *on the fly* di backend/frontend (jika diminta spesifik).
   - Tampilkan _progress state_ (loading) selama request.
   - Punya komponen untuk merender hasil (Success count, Skipped count, dan tabel/list Error).
2. **Tombol Trigger:** Tambahkan tombol **Import** bersebelahan dengan tombol **Export** di halaman `{module}/index.tsx`.
3. Gunakan state management via React Query (atau wrapper internal seperti `useMutation`) dengan endpoint `/api/{slug}/import`.

## 4. Testing (Pest & Playwright)

### Pest (`tests/Feature/{Modules}/{Module}ImportTest.php`)
- Group annotation: `->group('{slug}')` wajib ada di level atas (pest file).
- Test upload valid data.
- Test missing/invalid fields (mengembalikan validation error custom di array _summary_ baris).
- Test foreign key tidak valid (mengembalikan error _not found_ di baris).
- Test duplicate / Upsert logic.
- Test endpoint mereturn `422` jika bukan file valid.

### Playwright (`tests/e2e/{slug}/{module}.spec.ts`)
- Tambahkan test untuk men-trigger open modal import.
- (Opsional) simulasi file upload dan verifikasi API response ter-render di tabel summary hasil import.
