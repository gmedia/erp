---
description: Susun dan jalankan refactor plan berbasis data SonarQube lintas modul
---

# Workflow: Refactor Sonar-Driven

## Fokus Utama

Workflow ini khusus untuk menurunkan duplikasi kode secara terukur tanpa merusak konsistensi antar modul.

## 1. Ambil Baseline SonarQube (WAJIB)

Sumber data: **SonarCloud Web API** via `curl` (publik, tanpa token). Endpoint lengkap ada di `AGENTS.md` → **Quality Gate**.

Urutan eksekusi (`S=https://sonarcloud.io/api`, `K=gmedia_erp`):

1. Quality gate: `curl -s "$S/qualitygates/project_status?projectKey=$K" | jq '.projectStatus.status'`
2. Metrik inti: `curl -s "$S/measures/component?component=$K&metricKeys=duplicated_lines,duplicated_blocks,duplicated_lines_density,ncloc,coverage" | jq -c '.component.measures'`
3. Shortlist file cluster prioritas: `curl -s "$S/measures/component_tree?component=$K&metricKeys=duplicated_lines&qualifiers=FIL&s=metric&metricSort=duplicated_lines&asc=false&ps=20" | jq -r '.components[] | "\(.measures[0].value)\t\(.path)"'`
4. Drill-down hanya untuk file prioritas: `curl -s "$S/duplications/show?key=$K:<path>"`
5. Jika API tidak bisa diakses, jelaskan alasan singkat lalu pakai fallback: `.sonarcloud.properties`, `coverage.xml`, dan perubahan file Git untuk mendeteksi cluster duplikasi lintas modul.

Metrik minimum yang wajib dikumpulkan (dari SonarCloud API atau laporan setara):

- `duplicated_lines`
- `duplicated_blocks`
- `duplicated_lines_density`
- `ncloc`
- `coverage`

Catatan hemat token:

- Ambil ringkasan dulu, baru detail file yang masuk top duplicated clusters.
- Hindari fetch issue/log panjang tanpa filter modul.

## 2. Map Blast Radius Sebelum Refactor Struktural

Sebelum rename, move, split, merge, atau extraction lintas file:

- Petakan import/export dan file pemakai dengan `rg -n '{Symbol}' app routes resources/js tests database config` (lihat `/refactor-safe`).
- Jika wave mengubah struktur file, tulis daftar file yang akan disentuh sebelum edit.
- Jika pola duplikasi dipicu API package/framework eksternal, cek source versi terpasang di `vendor/`/`node_modules/` sebelum menentukan abstraction target.

## 3. Mapping ke Modul Registry

Petakan temuan ke modul berdasarkan slug pada `docs/module-registry.md`:

- `app/Http/Requests/Reports/*` -> kelompok `reports` (tetap pecah ke slug report saat eksekusi)
- `app/Http/Requests/{Module}/*` -> slug modul kebab-case
- `app/Domain/{Module}/*FilterService.php` -> slug modul domain terkait
- `app/Http/Controllers/*ItemController.php` -> modul transaksi induk (`stock-transfers`, `stock-adjustments`, `inventory-stocktakes`)

## 4. Kelompokkan Scope Refactor

Prioritaskan urutan berikut:

1. Cluster dengan duplicated lines terbesar
2. Cluster dengan duplicated line density tinggi (>= 70%)
3. Cluster yang punya pola berulang lintas modul (kandidat template/base class)

Batch per modul (jangan acak file lintas domain dalam 1 PR):

- Gunakan status terbaru dari `docs/refactor-sonar-progress.md`.
- Jika file progress belum ada, buat baseline progress dulu sebelum eksekusi batch.
- Pilih 1 batch aktif per PR agar review tetap fokus dan risiko regresi lebih rendah.

Aturan ukuran wave:

- Gunakan **semi-besar terkontrol**: 4-8 file per wave dengan pola refactor yang sama.
- Jika ada perubahan behavior query kompleks, turunkan ke 2-4 file per wave.
- Setelah 2 wave stabil (test pass + metrik membaik), boleh naikkan ukuran wave berikutnya.

## 5. Guard Konsistensi Antar Modul

Checklist wajib:

- API-only Laravel + React SPA (tanpa Inertia)
- Route backend hanya di `routes/api/*.php`
- Feature test auth pakai `Sanctum::actingAs(...)`
- Assertion API pakai `assertJson*`/`assertOk`
- Empty wrapper class tetap multiline + komentar intent
- Hindari FQCN di body executable PHP
- Untuk pattern yang sama (FilterService / FormRequest), gunakan shared abstraction yang seragam antar modul
- Untuk refactor style agent guidance, update `AGENTS.md` atau `.claude/` (source of truth)

Checklist wave:

- Jika extraction menyentuh banyak file, blast radius via `rg` dipetakan sebelum patch pertama.
- Jika wave dipicu aturan package/framework, behavior version-specific dicek di source `vendor/`/`node_modules/`.
- Untuk data project Laravel, pakai `sail artisan db:table` / `route:list` / `tinker`.

## 6. Eksekusi Refactor per Batch

Untuk tiap batch:

1. Extract pola duplikasi menjadi base class/trait/helper reusable
2. Terapkan pola yang sama ke seluruh modul dalam batch yang setara
3. Refactor internal tanpa ubah API contract
4. Tambah/rapikan Feature + Unit test modul tersebut
5. Jalankan formatter/lint sesuai standar project
6. Catat evidence blast radius (`rg`) di progress note bila dipakai untuk keputusan wave

Checklist anti-regresi wave:

- Tidak ada perubahan route, contract payload, dan response keys.
- Pola style/konstruksi yang diadopsi pada satu modul harus konsisten ke sibling module yang setara.
- Hindari campur refactor request + action + export dalam satu wave, kecuali skalanya kecil dan masih satu pola.

Contoh target extraction:

- `Index*Request` dan `Export*Request` report -> base request dengan daftar field override per modul
- `*FilterService` dengan pola where/like/date-range -> composable filter map builder
- Controller item transaksi (`*ItemController`) -> shared service untuk operasi item berulang

## 7. Verifikasi Wajib Setelah Perubahan

Gunakan Sail:

```bash
./vendor/bin/sail test --group {modul-names}
bash scripts/e2e-full.sh tests/e2e/{modul-names}
```

Catatan environment (penting):

- Jika `./vendor/bin/sail test --group ...` membaca argumen sebagai file test, jalankan fallback setara:

```bash
./vendor/bin/sail artisan test --group={modul-1} --group={modul-2}
```

Contoh:

```bash
./vendor/bin/sail test --group purchase-requests --group purchase-orders --group goods-receipts --group supplier-returns --group stock-adjustments --group stock-movements
bash scripts/e2e-full.sh tests/e2e/purchase-requests tests/e2e/purchase-orders tests/e2e/goods-receipts tests/e2e/supplier-returns tests/e2e/stock-adjustments tests/e2e/stock-movements

# fallback bila parser group gagal
./vendor/bin/sail artisan test --group=purchase-requests --group=purchase-orders --group=goods-receipts --group=supplier-returns --group=stock-adjustments --group=stock-movements
```

## 8. Exit Criteria

- Metrik `duplicated_lines` dan `duplicated_blocks` turun pada batch yang dikerjakan
- Metrik `duplicated_lines_density` project tidak naik setelah merge
- Tidak ada perubahan route/payload API publik
- Semua test batch (Pest + E2E) pass
- Jika SonarCloud API tidak bisa diakses, keputusan refactor tetap menyertakan baseline + evidence fallback yang bisa diaudit
- Jika refactor struktural dilakukan, evidence blast radius tercatat di progress note atau ringkasan wave

## 9. Catatan Anomali Sonar

Jika snapshot Sonar menunjukkan anomali (contoh `coverage = 0.0`) saat test lokal lulus:

1. Catat anomali di `docs/refactor-sonar-progress.md`.
2. Jangan langsung rollback refactor yang lulus test hanya karena anomali coverage snapshot tunggal.
3. Verifikasi ulang pada snapshot Sonar berikutnya setelah pipeline stabil.