# User Guide: Dashboard

> **Disambiguasi dashboard:**
> - **Dashboard** (`/dashboard`) — ringkasan jumlah master data (customer, employee, supplier, asset). Guide ini.
> - **Financial Dashboard** (`/financial-dashboard`) — KPI keuangan / YoY. Lihat `user-guide-financial-dashboard.md`.
> - **Aging Dashboard** (`/aging-dashboard`) — aging AR/AP. Lihat `user-guide-aging-dashboard.md`.
> - **Asset Dashboard** (`/asset-dashboard`) — distribusi & alert aset. Lihat `user-guide-asset-dashboard.md`.
> - **Pipeline Dashboard** (`/pipeline-dashboard`) — monitoring pipeline. Lihat guide pipeline.

## Gambaran Umum

Dashboard adalah halaman beranda aplikasi setelah login. Halaman ini menampilkan **empat kartu total** master data (bukan KPI keuangan). Data diambil dari `GET /api/dashboard` dan bersifat read-only.

Permission: `dashboard`.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Dashboard | `/dashboard` (root `/` mengarah ke sini) | Empat kartu total + shortcut operasional + mix master data |

## 1. Mengakses Dashboard

1. Login dengan akun yang punya permission `dashboard`.
2. Klik **Dashboard** di sidebar, atau buka `/dashboard`.
3. Tunggu data total termuat (angka tampil menggantikan `—` saat loading).

[Screenshot: Halaman Dashboard dengan 4 kartu total]

## 2. Kartu Total

| Kartu | Arti |
|-------|------|
| Total Customer | Jumlah customer di sistem |
| Total Employee | Jumlah employee |
| Total Supplier | Jumlah supplier |
| Total Asset | Jumlah asset |

Angka diformat sesuai pengaturan regional aplikasi.

## 3. Widget Operasional

Di bawah kartu ada dua panel:

| Panel | Isi |
|-------|-----|
| Shortcuts | Deep-link ke My Approvals, Purchase Orders, dan Stock Monitor |
| Master data mix | Bar relatif dari empat total yang sama (bukan KPI keuangan) |

Untuk KPI keuangan, gunakan **Financial Dashboard**.

## 4. Sumber Data & Error

- API: `GET /api/dashboard` → `data.totals` (`customers`, `employees`, `suppliers`, `assets`).
- Jika request gagal, kartu menampilkan `0` (fallback di frontend).

## FAQ

**Q: Kenapa tidak ada Revenue / Net Income di Dashboard?**  
A: Itu ada di **Financial Dashboard** (`/financial-dashboard`), bukan di beranda.

**Q: Apakah saya bisa filter tahun fiskal di sini?**  
A: Tidak. Filter FY ada di Financial Dashboard dan laporan keuangan.

**Q: Permission apa yang dibutuhkan?**  
A: `dashboard` untuk beranda; `financial_dashboard` untuk KPI keuangan.

## Lihat Juga

- `docs/user-guide-financial-dashboard.md`
- `docs/user-guide-aging-dashboard.md`
- `docs/user-guide-asset-dashboard.md`
