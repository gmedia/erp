---
description: Refactor plan berbasis data SonarQube MCP lintas modul
---

# Workflow: Refactor Sonar-Driven

## 1. Ambil Baseline SonarQube

Gunakan MCP Sonar, bukan query manual.

- Cari project key: `mcp_io_github_son_search_my_sonarqube_projects`
- Ambil metrik utama: `mcp_io_github_son_get_component_measures`
- Ambil issue prioritas: `mcp_io_github_son_search_sonar_issues_in_projects`
- Ambil file coverage rendah: `mcp_io_github_son_search_files_by_coverage`

## 2. Mapping ke Modul Registry

Petakan temuan ke modul berdasarkan slug pada `docs/module-registry.md`:

- `app/Http/Controllers/Auth/*` -> `auth`
- `app/Http/Controllers/Settings/*` -> `settings`
- `app/Http/Controllers/*ItemController.php` -> modul transaksi terkait (`stock-transfers`, `stock-adjustments`, `inventory-stocktakes`)
- `app/Actions/{Module}/...` atau `app/Http/Requests/{Module}/...` -> slug modul kebab-case

## 3. Kelompokkan Scope Refactor

Prioritaskan urutan berikut:

1. Security/reliability issue (HIGH/BLOCKER)
2. File 0% coverage pada controller/action utama
3. File coverage < 60% dengan branch logic kompleks

Batch per modul (jangan acak file lintas domain dalam 1 PR):

- Batch A: `auth`, `settings`, `users`
- Batch B: `asset-stocktakes`, `asset-depreciation-runs`
- Batch C: `stock-transfers`, `stock-adjustments`, `stock-movements`
- Batch D: `approval-flows`, `approval-delegations`, `entity-state-actions`

## 4. Guard Konsistensi Antar Modul

Checklist wajib:

- API-only Laravel + React SPA (tanpa Inertia)
- Route backend hanya di `routes/api/*.php`
- Feature test auth pakai `Sanctum::actingAs(...)`
- Assertion API pakai `assertJson*`/`assertOk`
- Empty wrapper class tetap multiline + komentar intent
- Hindari FQCN di body executable PHP

## 5. Eksekusi Refactor per Batch

Untuk tiap batch:

1. Refactor internal tanpa ubah API contract
2. Tambah/rapikan Feature + Unit test modul tersebut
3. Jalankan formatter/lint sesuai standar project

## 6. Verifikasi Wajib Setelah Perubahan

Gunakan Sail:

```bash
./vendor/bin/sail test --group {modul-names}
./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/
```

Contoh:

```bash
./vendor/bin/sail test --group stock-movements --group stock-adjustments --group stock-transfers
./vendor/bin/sail npx playwright test tests/e2e/stock-movements/ tests/e2e/stock-adjustments/ tests/e2e/stock-transfers/
```

## 7. Exit Criteria

- Tidak ada issue OPEN severity HIGH/BLOCKER pada batch yang dikerjakan
- Coverage batch naik dan tidak menurunkan quality gate project
- Tidak ada perubahan route/payload API publik
- Semua test batch (Pest + E2E) pass