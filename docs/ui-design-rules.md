# Aturan Design UI — Konsistensi Antar Modul

Dokumen ini adalah **sumber kebenaran tunggal** untuk konsistensi UI di ERP ini. Dipakai oleh agent untuk dua tujuan:

1. **Membuat UI baru** — ikuti aturan sejak awal agar tidak menciptakan outlier baru.
2. **Me-review & mengoreksi UI existing** — pakai checklist untuk menemukan dan memperbaiki inkonsistensi.

Prinsip utama: **pakai shell/komponen bersama, jangan bikin pola baru kecuali ada alasan yang terdokumentasi.** Mayoritas inkonsistensi di `docs/visual-audit/FINDINGS.md` (SHELL-01…12, HF-1…3) terjadi karena halaman "sendiri" tidak memakai shared shell.

---

## 1. Sumber kebenaran & tempat melihat

| Kebutuhan | Lokasi |
|-----------|--------|
| Aturan struktur frontend | `resources/js/AGENTS.md` |
| Page shell CRUD | `components/common/EntityCrudPage.tsx` (`createEntityCrudPage`) |
| Page shell report | `components/common/ReportDataTablePage.tsx` |
| Header halaman | `components/common/PageHeader.tsx` |
| Tabel + sticky actions | `components/common/DataTableCore.tsx` |
| Modal lihat | `components/common/ViewModalShell.tsx` + `ViewField` |
| Form | `hooks/useEntityForm.ts` + `components/common/EntityForm.tsx` |
| Primitif UI | `components/ui/` (Shadcn) |
| Temuan audit nyata | `docs/visual-audit/FINDINGS.md`, `docs/visual-audit/BACKLOG.md` |

Jangan menebak struktur. Baca file di atas sebelum menulis UI baru atau mengoreksi yang lama.

---

## 2. Aturan untuk pembuatan UI baru

### 2.1 Page shell (wajib)
- **Simple CRUD** → `createEntityCrudPage(config)` di `pages/{slug}/index.tsx` (±6 baris). Jangan menulis halaman CRUD manual.
- **Complex CRUD** → factory yang sama + 4 file sibling: `{Module}Columns.tsx`, `{Module}Filters.tsx`, `{Module}Form.tsx`, `{Module}ViewModal.tsx`.
- **Report** → `ReportDataTablePage` / `FinancialReportPageShell`. Jangan pakai `DataTablePage` biasa untuk laporan keuangan.
- **Dashboard** → shells yang sudah ada (`Dashboard`, `FinancialDashboard`, `AssetDashboard`, `AgingDashboard`, `PipelineDashboard`, `StockMonitor`) masing-masing terpisah. Jangan redirect `/` ke financial dashboard.

### 2.2 Header halaman (kontrak PageHeader)
- Satu **H1 + deskripsi singkat** per halaman. Jangan double title (breadcrumb + H1 sama — lihat ACC-01, FD-06).
- Breadcrumb mencerminkan lokasi navigasi, bukan judul halaman.
- Judul di modal/form memakai pola `ViewModalShell` / `EntityForm`, bukan heading halaman.

### 2.3 Toolbar (kontrak satu baris)
- Search di kiri, aksi (Export, Create, dll.) di kanan, **satu baris**. Jangan dua baris dengan "Columns" orphan (SHELL-02, EMP-02).
- Urutan aksi konsisten: filter → columns → export → create.
- Jangan menyisipkan bulk-action tanpa UX checkbox yang jelas (SHELL-10).

### 2.4 Tabel data
- **Kolom Actions selalu sticky di kanan** (`sticky right-0`, lihat `DataTableCore.tsx` konstanta `STICKY_ACTIONS_CELL/HEAD`). Jangan biarkan terpotong (EMP-01, SHELL-07, PO-04).
- Grand Total (bila ada) sticky tepat di kiri Actions (`STICKY_GRAND_TOTAL_*`), jangan ditaruh di tengah (PO-05).
- Tabel sparse: jangan biarkan kanvas putih besar di bawah baris sedikit (SHELL-05, RSM-01) — sesuaikan density/empty-state.
- Pagination konsisten di footer (SHELL-11).
- `data-testid` yang dipakai E2E **tidak boleh diubah** tanpa update test.

### 2.5 Status & warna semantik
- Status badge **harus bermakna**: jangan semua hitam/outline (SHELL-06, EMP-03, PO-01).
- Warna mengikuti semantik:
  - sukses/positif = hijau/teal,
  - gagal/negatif/expired = merah/rose,
  - warning/pending = amber,
  - netral/draft = gray.
- **Angka negatif tidak boleh memakai warna sukses** (HF-1, FD-01). Pakai helper `getSignedAmountTextClass`.
- Pisahkan warna status dokumen dari warna amount.

### 2.6 Form
- Prop form selalu bernama `entity` (never `product`, `customer`, dll.).
- Validasi Zod via schema; `useEntityForm` untuk boilerplate.
- Field opsional ditandai jelas; jangan asumsi required.
- Input tanggal/angka memakai format konsisten dengan modul sejenis.

### 2.7 Modal
- ViewModal selalu `memo()` + `ViewModalShell` + `ViewField`.
- Padding/title/ruang modal mengikuti `ViewModalShell` (EX-modal), jangan custom per modul.
- Tutup/escape/focus behavior konsisten dengan modal lain.

### 2.8 Layout & density
- Sidebar: label tidak terpotong (SHELL-01), item aktif sesuai rute (SHELL-08, HF-3).
- Nested navigation panjang: child aktif mudah terlihat (SHELL-09).
- Gunakan Tailwind 4 utility + `tailwind-merge`; jangan inline style warna/spacing yang bisa memakai token.
- Responsif: uji viewport 1440×900 (baseline audit) dan viewport sempit untuk overflow horizontal.

### 2.9 Meta, routing, data
- `react-helmet-async` di-import dari alias lokal (`resources/js/lib/`), **jangan** tambahkan dependency upstream.
- Route di `app-routes.tsx` semua `React.lazy()`.
- Fetching via `useCrudQuery`/`useCrudMutations`; **jangan axios langsung di komponen**.
- Title meta mengikuti judul halaman yang sama dengan H1.

---

## 3. Checklist review & koreksi UI existing

Gunakan checklist ini saat diminta "review UI", "cek konsistensi modul X", atau "perbaiki tampilan fitur Y". Tandai PASS/FAIL per item.

### A. Identifikasi dulu
- [ ] Modul/route mana yang direview? Buka `docs/module-registry.md` untuk metadata.
- [ ] Apakah halaman memakai shared shell yang benar (CRUD/report/dashboard)?
- [ ] Baca temuan audit terkait di `FINDINGS.md` (ID SHELL-/HF-/EX-) — jangan ulangi bug yang sudah pernah ditemukan.

### B. Page chrome
- [ ] H1 + deskripsi tunggal, tidak double title dengan breadcrumb.
- [ ] Breadcrumb sesuai lokasi, bukan judul.
- [ ] Sidebar active state benar.

### C. Toolbar
- [ ] Search kiri, aksi kanan, satu baris.
- [ ] Tidak ada orphan "Columns"; urutan aksi konsisten.
- [ ] Bulk action hanya jika checkbox tersedia dan jelas.

### D. Tabel
- [ ] Actions sticky kanan dan terlihat tanpa scroll horizontal berlebih.
- [ ] Grand Total sticky di kiri Actions (jika ada).
- [ ] Baris sedikit tidak menghasilkan kanvas kosong besar.
- [ ] Pagination terlihat di footer.

### E. Semantik warna
- [ ] Status badge tidak seragam hitam/outline; state penting "pop".
- [ ] Angka negatif rose/red, bukan hijau.
- [ ] Warna status dokumen ≠ warna amount.

### F. Form & modal
- [ ] Prop `entity`, validasi Zod, `useEntityForm`.
- [ ] ViewModal `memo()` + `ViewModalShell` + `ViewField`.
- [ ] Padding/title modal mengikuti shell bersama.

### G. Teknis
- [ ] Tidak ada axios langsung; pakai hooks.
- [ ] Tidak ada import Inertia; helmet dari shim lokal.
- [ ] `data-testid` E2E tidak berubah tanpa update spec.
- [ ] `npm run types` & ESLint targeted lulus.

### H. Cara koreksi
1. **Perbaiki di shared component dulu** kalau masalahnya sistemik (≥3 halaman) — misal sticky Actions di `DataTableCore`. Jangan patch per halaman.
2. Kalau hanya satu halaman outlier, migrasikan ke shell bersama (contoh: `DataTablePage` → `ReportDataTablePage`).
3. Tambahkan baris delta ke `docs/visual-audit/FINDINGS.md` / `BACKLOG.md` bila menemukan masalah baru.

---

## 4. Aturan emas

> Satu pola UI per kebutuhan. Kalau Anda menulis JSX layout/table/form yang tidak ada di shared component, kemungkinan besar Anda harus **memakai atau memperbaiki shared component**, bukan membuat yang baru.

Pengecualian harus dicatat di `docs/visual-audit/FINDINGS.md` dengan alasan, supaya agent berikutnya tidak mengira itu pola baku.

## Mandatory: shadcn-ui components

Semua kontrol UI wajib memakai komponen shadcn-ui yang sudah ada di `resources/js/components/ui/*`:

- `Button`, `Input`, `Textarea`, `Select`, `Checkbox`, `Dialog`, `Tabs`, `DropdownMenu`, `Calendar`, `Popover`, dll.
- Jangan memakai elemen HTML mentah (`<button>`, `<input type="file">`, `<select>`) untuk kontrol interaktif jika ada padanan shadcn.
- Komponen khusus (date-time picker, upload foto, QR panel) harus berupa wrapper yang tetap memakai primitives shadcn.
- Jangan menambah UI library baru tanpa persetujuan.
