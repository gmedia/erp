# Aturan Design UI — Konsistensi Antar Modul

Dokumen ini adalah **sumber kebenaran tunggal** untuk konsistensi UI di ERP ini. Dipakai oleh agent untuk dua tujuan:

1. **Membuat UI baru** — ikuti aturan sejak awal agar tidak menciptakan outlier baru.
2. **Me-review & mengoreksi UI existing** — pakai checklist untuk menemukan dan memperbaiki inkonsistensi.

Prinsip utama: **pakai shell/komponen bersama, jangan bikin pola baru kecuali ada alasan yang terdokumentasi.** Mayoritas inkonsistensi di `docs/visual-audit/FINDINGS.md` (SHELL-01…12, HF-1…3) terjadi karena halaman "sendiri" tidak memakai shared shell.

---

## 0. Bahasa visual (light & dark)

Gaya: **tenang, datar, padat informasi**. Halaman hampir putih (light) / hampir hitam (dark); pemisahan dibuat dengan **permukaan terisi**, bukan garis dan bayangan. Warna hanya untuk makna (status, uang, angka), bukan dekorasi. Light dan dark wajib setara — setiap halaman diuji di kedua mode.

### 0.1 Token (sumber: `resources/css/app.css`)

Jangan menulis hex/rgb/`oklch` di komponen. Pakai utility token Tailwind (`bg-card`, `text-success`, dst.).

| Peran                                            | Utility                                              | Light                                                               | Dark                    |
| ------------------------------------------------ | ---------------------------------------------------- | ------------------------------------------------------------------- | ----------------------- |
| Halaman, sidebar, popover                        | `bg-background` / `bg-sidebar`                       | `#fdfdfd`                                                           | `#070707`               |
| Permukaan (card, panel, KPI, item aktif sidebar) | `bg-card` / `bg-sidebar-accent`                      | `#f7f7f5`                                                           | `#121212`               |
| Kontrol sekunder, segmented track, chip, select  | `bg-secondary` / `bg-muted`                          | `#f4f3f2`                                                           | `#181818`               |
| Tombol utama                                     | `bg-primary text-primary-foreground`                 | hitam `#060606`                                                     | terang `#f2f2f2`        |
| Garis pemisah baris tabel                        | `border-border`                                      | sangat halus                                                        | sangat halus            |
| Teks sekunder / meta                             | `text-muted-foreground`                              | abu                                                                 | abu                     |
| Sukses, nilai uang, status aktif                 | `text-success`, `bg-success-soft`                    | emerald-600 / `#e4f7f0`                                             | emerald-400 / `#061912` |
| Peringatan, banner info                          | `text-warning`, `bg-warning-soft`                    | amber-700 / `#fdf3e4`                                               | amber-400 / `#1f1506`   |
| Destruktif                                       | `text-destructive-foreground`, `bg-destructive-soft` | merah / `#f9e6e7`                                                   | merah / `#1e0e0d`       |
| Angka hitungan, tautan                           | `text-info`                                          | sky-600                                                             | sky-400                 |
| Chart                                            | `chart-1` … `chart-5`                                | emerald-500 (utama), sky, violet, amber, hijau lembut (batang/area) | sama, disesuaikan gelap |

Perubahan warna dilakukan **di token**, bukan per halaman. Branding per perusahaan (on-premise) tidak boleh mengubah warna semantik (sukses/gagal/peringatan).

### 0.2 Bentuk & permukaan

- **Card/panel**: `Card` tanpa border dan tanpa bayangan — terisi `bg-card`, `rounded-xl`. Jangan menambah `border`/`shadow-*` pada card.
- **Kontrol berbentuk pill** (`rounded-full`): `Button` semua varian, `Badge`, `TabsList`/`TabsTrigger` (segmented 24h/7d/30d), chip filter. Input, select, dan textarea tetap `rounded-md`.
- **Kontrol teks** (`ui/input`, `ui/textarea`, kolom search toolbar): tinggi `h-8`, teks 14px, terisi `bg-secondary` tanpa border saat diam (`border-transparent`), tanpa `shadow-xs`. Fokus: ring 2px `ring-ring/40` tanpa border aksen. Error (`aria-invalid`): ring 2px `ring-destructive/40`. Disabled: `opacity-50`. Search toolbar memakai `rounded-lg`. Jangan menimpa dengan `h-9`, `border-border`, atau `bg-background` di pemakai.
- **Hierarki tombol**: satu `default` (hitam/terang) per area untuk aksi utama (Create, Save, Connect); aksi lain `secondary` (pill abu); aksi berbahaya `destructive-soft` (pill merah lembut + ikon). `destructive` solid hanya di dialog konfirmasi.
- Tanpa bayangan pada komponen datar; bayangan hanya untuk lapisan melayang (popover, dropdown, dialog).

### 0.3 Tipografi & angka

- H1 halaman: `text-2xl font-semibold`, tanpa ikon dekoratif.
- Judul card: `text-base font-semibold`; label KPI: `text-sm text-muted-foreground`.
- Angka KPI: `text-3xl font-semibold tabular-nums` + satu baris meta kecil di bawahnya (mis. "96,3% sukses · 14 gagal").
- Angka di tabel: rata kanan, `tabular-nums`. Nilai uang `text-success` hanya jika positif (lihat §2.5).

### 0.4 Pola halaman

- **Header**: H1 kiri; kontrol rentang waktu + tombol `Refresh` (pill `secondary`)/aksi utama di kanan, **satu baris** — label filter `sr-only` (nama aksesibel tetap), prefix ringkas di dalam kontrol bila perlu (`FilterDatePicker inlineLabel`, `vs` pada pembanding). Segmented pill (24h/7d/30d) hanya bila backend mendukung rentang relatif; dashboard berbasis tahun fiskal / as-of date memakai Select/DatePicker.
- **Baris KPI**: 4–5 card setara dalam satu grid (`grid-cols-2 lg:grid-cols-4/5`), ikon kecil `text-muted-foreground` di pojok kanan atas tiap card.
- **Banner info/peringatan**: full-width di bawah header, `bg-warning-soft`, judul tebal + meta, aksi berupa tautan teks di kanan. Wajib memakai `components/common/PageBanner.tsx` (`tone`: `info`/`warning`/`success`/`destructive`, `title`, `action`); jangan merakit `Alert` dengan kelas tint manual.
- **Tabel**: tanpa garis vertikal dan tanpa zebra; pemisah baris tipis; baris longgar. Sel utama dua baris (nama tebal + meta `text-xs text-muted-foreground`); status sebagai `Badge` (`success`/`warning`/`destructive-soft`/`outline`); aksi baris lewat menu `⋯` di kolom terakhir (sticky, §2.4); header kolom bisa disortir dengan ikon kecil. Implementasi: kolom nama/kode utama `createTextColumn({ …, primary: true })`, meta baris kedua lewat opsi `description: (row) => …` (jangan menggabung/menghapus kolom yang sudah ada — header, visibility, dan export adalah kontrak); menu aksi = `GenericActions`/`createActionsColumn` (ikon `⋯`, nama aksesibel "Actions"). Modul contoh yang sudah memakai `description`: Purchase Orders (nomor PO → supplier), Customers & Suppliers (nama → kategori), Employees (nama → jabatan · departemen), Products (nama → kategori), Assets (nama → kategori · model). Hanya field yang sudah ada di payload list.
- **Toolbar tabel**: search (input terisi) + `Filters` (pill sekunder) di kiri; aksi massal pill sekunder + aksi destruktif `destructive-soft` di kanan (§2.3).
- **Settings**: navigasi seksi vertikal di kiri (item aktif `bg-card`); di kanan tiap baris = label tebal + deskripsi `text-muted-foreground` di kiri, kontrol di kanan; `Save` (pill utama) + reset (ikon) di kanan atas. Wajib memakai `components/common/settings.tsx`: `SettingsShell` (layout + nav), `SettingsSection` (judul + `actions`), `SettingsRow` (label/deskripsi/error kiri, kontrol kanan), `SettingsSaveButton` (submit via atribut `form`).
- **Dashboard**: grid card (chart, distribusi, heatmap aktivitas, ketersediaan) memakai `chart-1` sebagai seri utama; legenda berupa titik warna kecil.

### 0.5 Mode gelap

- Semua warna lewat token; **jangan** menulis `dark:bg-…` manual kecuali untuk kasus khusus yang tidak bisa diwakili token (catat alasannya).
- Kontras dicek di kedua mode: teks meta tetap terbaca, status soft tetap terbedakan dari permukaan.

---

## 1. Sumber kebenaran & tempat melihat

| Kebutuhan                | Lokasi                                                          |
| ------------------------ | --------------------------------------------------------------- |
| Aturan struktur frontend | `resources/js/AGENTS.md`                                        |
| Page shell CRUD          | `components/common/EntityCrudPage.tsx` (`createEntityCrudPage`) |
| Page shell report        | `components/common/ReportDataTablePage.tsx`                     |
| Header halaman           | `components/common/PageHeader.tsx`                              |
| Tabel + sticky actions   | `components/common/DataTableCore.tsx`                           |
| Modal lihat              | `components/common/ViewModalShell.tsx` + `ViewField`            |
| Form                     | `hooks/useEntityForm.ts` + `components/common/EntityForm.tsx`   |
| Primitif UI              | `components/ui/` (Shadcn)                                       |
| Temuan audit nyata       | `docs/visual-audit/FINDINGS.md`, `docs/visual-audit/BACKLOG.md` |

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
- Pakai varian/token, bukan class warna mentah: `Badge variant="success" | "warning" | "destructive-soft" | "outline"`, teks `text-success` / `text-warning` / `text-destructive-foreground` / `text-info` (lihat §0.1).
- Ditegakkan CI: `npm run check:raw-colors` gagal bila ada kelas palet Tailwind mentah (`text-red-500`, `bg-emerald-100`, `decoration-neutral-300`, …) di `resources/js`. Pengecualian hanya lewat allowlist di `scripts/check-raw-colors.mjs` beserta alasannya.
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
- Sidebar: setiap menu dan child menu wajib punya ikon yang terdaftar di `resources/js/lib/icon-map.ts` (import + entry). Jalankan `npm run check:menu-icons` setelah mengubah menu.
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
- [ ] Semua menu + child menu punya ikon; `npm run check:menu-icons` lulus tanpa missing.

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

### H. Bahasa visual (§0)

- [ ] Tidak ada hex/rgb/`oklch` atau `dark:bg-…` manual di komponen; semua lewat token.
- [ ] `npm run check:raw-colors` lolos (0 kelas palet mentah).
- [ ] Card tanpa border/bayangan; kontrol (Button, Badge, Tabs, chip) berbentuk pill.
- [ ] Satu tombol utama per area; aksi berbahaya `destructive-soft`.
- [ ] Angka KPI/tabel `tabular-nums`; tabel tanpa garis vertikal/zebra; aksi baris lewat menu `⋯`.
- [ ] Dicek di light **dan** dark.

### I. Cara koreksi

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
