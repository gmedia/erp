---
description: Susun dan jalankan refactor plan berbasis data SonarQube lintas modul
---

# Workflow: Refactor Sonar-Driven

## Fokus Utama

Workflow ini khusus untuk menurunkan duplikasi kode secara terukur tanpa merusak konsistensi antar modul.

## 1. Ambil Baseline SonarQube (WAJIB)

Selalu cek MCP server yang tersedia lebih dulu.

Urutan eksekusi:

1. Jika Sonar MCP tersedia, gunakan tool Sonar untuk ambil metrik duplikasi.
2. Jika Sonar MCP tidak tersedia, jelaskan alasan singkat lalu gunakan fallback berikut:
	- Config Sonar: `.sonarcloud.properties`
	- Laporan coverage: `coverage.xml`
	- Perubahan file dari Git untuk mendeteksi cluster duplikasi lintas modul
3. Untuk data aplikasi Laravel, prioritaskan Laravel Boost MCP (schema/routes/log/docs) sesuai kebutuhan.

Metrik minimum yang wajib dikumpulkan (dari Sonar MCP atau laporan setara):

- `duplicated_lines`
- `duplicated_blocks`
- `duplicated_lines_density`
- `ncloc`
- `coverage`

Catatan hemat token:

- Ambil ringkasan dulu, baru detail file yang masuk top duplicated clusters.
- Hindari fetch issue/log panjang tanpa filter modul.

## 2. Mapping ke Modul Registry

Petakan temuan ke modul berdasarkan slug pada `docs/module-registry.md`:

- `app/Http/Requests/Reports/*` -> kelompok `reports` (tetap pecah ke slug report saat eksekusi)
- `app/Http/Requests/{Module}/*` -> slug modul kebab-case
- `app/Domain/{Module}/*FilterService.php` -> slug modul domain terkait
- `app/Http/Controllers/*ItemController.php` -> modul transaksi induk (`stock-transfers`, `stock-adjustments`, `inventory-stocktakes`)

## 3. Kelompokkan Scope Refactor

Prioritaskan urutan berikut:

1. Cluster dengan duplicated lines terbesar
2. Cluster dengan duplicated line density tinggi (>= 70%)
3. Cluster yang punya pola berulang lintas modul (kandidat template/base class)

Batch per modul (jangan acak file lintas domain dalam 1 PR):

- Gunakan status terbaru dari `docs/refactor-sonar-progress.md`.
- Jika file progress belum ada, buat baseline progress dulu sebelum eksekusi batch.
- Pilih 1 batch aktif per PR agar review tetap fokus dan risiko regresi lebih rendah.

## 4. Guard Konsistensi Antar Modul

Checklist wajib:

- API-only Laravel + React SPA (tanpa Inertia)
- Route backend hanya di `routes/api/*.php`
- Feature test auth pakai `Sanctum::actingAs(...)`
- Assertion API pakai `assertJson*`/`assertOk`
- Empty wrapper class tetap multiline + komentar intent
- Hindari FQCN di body executable PHP
- Untuk pattern yang sama (FilterService / FormRequest), gunakan shared abstraction yang seragam antar modul
- Untuk refactor style agent guidance, update di folder `.github` (source of truth)

## 5. Eksekusi Refactor per Batch

Untuk tiap batch:

1. Extract pola duplikasi menjadi base class/trait/helper reusable
2. Terapkan pola yang sama ke seluruh modul dalam batch yang setara
3. Refactor internal tanpa ubah API contract
4. Tambah/rapikan Feature + Unit test modul tersebut
5. Jalankan formatter/lint sesuai standar project

Contoh target extraction:

- `Index*Request` dan `Export*Request` report -> base request dengan daftar field override per modul
- `*FilterService` dengan pola where/like/date-range -> composable filter map builder
- Controller item transaksi (`*ItemController`) -> shared service untuk operasi item berulang

## 6. Verifikasi Wajib Setelah Perubahan

Gunakan Sail:

```bash
./vendor/bin/sail test --group {modul-names}
./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/
```

Catatan environment (penting):

- Jika `./vendor/bin/sail test --group ...` membaca argumen sebagai file test, jalankan fallback setara:

```bash
./vendor/bin/sail artisan test --group={modul-1} --group={modul-2}
```

Contoh:

```bash
./vendor/bin/sail test --group purchase-requests --group purchase-orders --group goods-receipts --group supplier-returns --group stock-adjustments --group stock-movements
./vendor/bin/sail npx playwright test tests/e2e/purchase-requests/ tests/e2e/purchase-orders/ tests/e2e/goods-receipts/ tests/e2e/supplier-returns/ tests/e2e/stock-adjustments/ tests/e2e/stock-movements/

# fallback bila parser group gagal
./vendor/bin/sail artisan test --group=purchase-requests --group=purchase-orders --group=goods-receipts --group=supplier-returns --group=stock-adjustments --group=stock-movements
```

## 7. Exit Criteria

- Metrik `duplicated_lines` dan `duplicated_blocks` turun pada batch yang dikerjakan
- Metrik `duplicated_lines_density` project tidak naik setelah merge
- Tidak ada perubahan route/payload API publik
- Semua test batch (Pest + E2E) pass
- Jika Sonar MCP tidak tersedia, keputusan refactor tetap menyertakan baseline + evidence fallback yang bisa diaudit