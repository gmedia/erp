# Visual Audit — Backlog

**Last updated:** 2026-11-22 (#319 — audit ulang wave-5)  
**Source:** multimodal-looker Wave 0–1; audit light/dark × desktop/mobile 85 route (#296, lihat `FINDINGS.md`)  
**Policy:** T1–T5 + EX themes on main. No **mass** Wave 2 (85 leaves). Prefer selective re-smoke via `VISUAL_AUDIT_PRESET` / `VISUAL_AUDIT_ROUTES`.

## Themes

| ID               | Theme                  | Status               |
| ---------------- | ---------------------- | -------------------- |
| T1–T5            | Shared shell           | **merged** (#90–#94) |
| Closeout         | BACKLOG / task handoff | **merged** (#95)     |
| Harness          | Named presets          | **merged** (#97)     |
| EX-1             | My Approvals inbox     | **merged** (#96)     |
| EX-auth          | Capture auth settle    | **merged** (#98)     |
| EX-asset-profile | Asset profile chrome   | **merged** (#99)     |
| EX-modal         | View/form modal chrome | **merged** (#100)    |

## Wave 5 fix groups (dari `FINDINGS.md` W5-xx, #319)

Kerjakan berurutan G1 → G7.

| Grup  | Pri | Temuan                                          | Scope                                                             | Status                     |
| ----- | --- | ----------------------------------------------- | ----------------------------------------------------------------- | -------------------------- |
| W5-G1 | P1  | W5-09, W5-10, W5-11                             | Formatter & badge: nilai mentah, tone status, format uang/tanggal | **merged #328** |
| W5-G2 | P1  | W5-01, W5-02, W5-03                             | Tabel: kolom kunci tertutup, fade report, aksi sticky mobile      | **merged #329**            |
| W5-G3 | P1  | W5-05, W5-06, W5-07                             | Page shell: breadcrumb, shell custom, KPI/toolbar dashboard       | **merged #330**            |
| W5-G4 | P1  | W5-04, W5-08                                    | Report: laporan keuangan mobile, empty state i18n + mobile        | **merged #331**            |
| W5-G5 | P1  | W5-13, W5-14, W5-15, W5-16, W5-17, W5-18, W5-19 | Logic report/modul yang salah informasi                           | **merged #PRX**            |
| W5-G6 | P2  | W5-20, W5-21, W5-22, W5-23                      | Seeder: tanggal, GL vs subledger, kode/nama, variasi data         | open #325                  |
| W5-G7 | P3  | W5-12                                           | Polish UI kecil                                                   | open #326                  |

## Wave 4 fix groups (dari `FINDINGS.md` W4-xx)

| Grup  | Pri | Temuan                            | Scope                                                                     | Status          |
| ----- | --- | --------------------------------- | ------------------------------------------------------------------------- | --------------- |
| W4-G1 | P1  | W4-01, W4-02                      | Badge status: label humanize + tone semantik terpusat                     | **merged #310** |
| W4-G2 | P1  | W4-03, W4-10                      | Tabel: tepi scroll kanan, kolom sticky, header non-sortable               | **merged #311** |
| W4-G3 | P1  | W4-04, W4-05, W4-06               | Page shell: PageHeader CRUD, shell custom, breadcrumb                     | **merged #312** |
| W4-G4 | P1  | W4-07, W4-08, W4-18               | Dashboard: KPI terpotong, filter satu baris, pipeline dashboard           | **merged #313** |
| W4-G5 | P2  | W4-09, W4-11, W4-12, W4-14        | Aksi baris, formatter nilai mentah, link email/telepon, upload logo       | **merged #316** |
| W4-G6 | P2  | W4-13, W4-15, W4-16, W4-17        | Font, toolbar mobile, sidebar footer, My Approvals                        | **merged #317** |
| W4-G7 | P1  | W4-19, W4-20, W4-21, W4-22, W4-24 | Report: query sebelum pilihan, pagination, comparative, empty state, i18n | **merged #314** |
| W4-G8 | P2  | W4-23                             | Seeder & logic report yang membuat data tampil tidak konsisten            | **merged #315** |

## Exception rows

| ID               | Pri | Theme                  | Scope                       | Status          | Notes                             |
| ---------------- | --- | ---------------------- | --------------------------- | --------------- | --------------------------------- |
| EX-1             | P1  | My Approvals chrome    | `/my-approvals`             | **merged #96**  | Densify inbox                     |
| EX-auth          | P1  | Capture auth settle    | visual harness              | **merged #98**  | `requireDashboard` + re-login     |
| EX-asset-profile | P1  | Asset profile chrome   | `/assets/:ulid`             | **merged #99**  | PageHeader + compact tabs/cards   |
| EX-modal         | P1  | View/form modal chrome | ViewModalShell + EntityForm | **merged #100** | Tighter padding, title, ViewField |

## Hotfixes (can ship before full T1)

| ID   | Pri | Item                                                                  | Status         |
| ---- | --- | --------------------------------------------------------------------- | -------------- |
| HF-1 | P0  | Financial dashboard: negative Cash Balance must not use success green | done (fix/hf1) |
| HF-2 | P0  | Employees (and wide tables): sticky Actions + horizontal scroll       | done (fix/hf2) |
| HF-3 | P1  | Accounts: fix sidebar active state for Chart of Accounts              | done (fix/hf3) |

## Done

| ID            | Notes                                                                                                   |
| ------------- | ------------------------------------------------------------------------------------------------------- |
| VA-W0-SETUP   | harness, url-list, visual-audit config, smoke                                                           |
| VA-W1-CAPTURE | 7 routes PNG                                                                                            |
| VA-W1-VISION  | multimodal-looker full audit; stop-rule YES                                                             |
| T1            | PR #90 — DataTable shell v2                                                                             |
| T2            | PR #91 — Sidebar density + active                                                                       |
| T3            | PR #92 — Page header contract                                                                           |
| T4            | PR #93 — Dashboard & KPI semantics                                                                      |
| T5            | PR #94 — Sparse & report density (SHELL-05; RSM-01)                                                     |
| BS-02         | Signed amounts rose for negatives (`FinancialReportSection`)                                            |
| FD-03         | KPI vs-comparison hidden when Compare is None                                                           |
| FD-06         | Breadcrumb + H1 both “Financial Overview”                                                               |
| RSM-02        | Stock Movements page has title + description                                                            |
| G1            | PR #260 — token light/dark + komponen pill (bahasa visual baru)                                         |
| G2            | PR #269 — tabel datar, badge status semantik, KPI tanpa aksen                                           |
| G3            | PR #278, #279, #280 — token warna komponen bersama, dashboard, vehicle loans + sisa modul               |
| G4            | PR #288 (Settings dua kolom), #291 (header dashboard + `PageBanner`), #292 (aksi baris `⋯` + sel utama) |
| VA-W4-AUDIT   | #296 — 340 capture light/dark × desktop/mobile, 24 temuan W4-xx                                         |
| VA-W5-AUDIT   | #319 — audit ulang 340 capture pasca W4-G1…G8, 23 temuan W5-xx → 7 grup                                 |

## Residual / optional (not shared-shell themes)

| ID       | Pri | Item                             | Notes                                                                                                                      |
| -------- | --- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| PO-05    | P2  | Grand Total column visibility    | **merged #107** — sticky when `grand_total` is immediately left of Actions (PO, credit notes; not invoice/bill Amount Due) |
| BS-01    | P2  | Compare “None” label opacity     | **merged #102** — labeled Fiscal Year / Compare; None muted                                                                |
| SHELL-12 | P3  | Home stub vs financial dashboard | **merged #108** — shortcuts + master-data mix; keep separate FD route/permission                                           |
| FD-02    | P1  | FY “Select…” while KPIs full     | **merged #104**                                                                                                            |

## Implementation rules

- One theme ≈ one `feat/*` or `fix/*` MR (AGENTS.md)
- Prefer shared chrome over per-module hacks
- Re-capture only **exception** surfaces later — not 85 leaves
- PNGs stay gitignored; cite paths in MR description
- Agents: **AGENTS.md Tool & Process Concurrency** (≤3 tools/turn; post-kill = 1 tool/turn)

## Harness route selection

| Priority | Env                          | Effect                                                                                |
| -------- | ---------------------------- | ------------------------------------------------------------------------------------- |
| 1        | `VISUAL_AUDIT_ROUTES=/a,/b`  | Comma list only                                                                       |
| 2        | `VISUAL_AUDIT_PRESET=shells` | Named set in `presets.json` (`shells`, `wave-0`, `exceptions`, `dashboards`, `smoke`) |
| 3        | (none)                       | Full `url-list.json` (85) — avoid on low-RAM hosts                                    |

```bash
VISUAL_AUDIT=1 VISUAL_AUDIT_PRESET=shells PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
  npx playwright test -c playwright.visual-audit.config.ts
```

Matriks tema/viewport (opsional): `VISUAL_AUDIT_THEMES=light,dark VISUAL_AUDIT_VIEWPORTS=desktop,mobile` → `<slug>--<theme>--<viewport>.png` + `report.jsonl` (overflow horizontal, kelas `.dark`, error console). Hentikan Vite dev dan `npm run build` dulu; jangan commit PNG/`report.jsonl`.

## Next agent action

0. **2026-11-22:** kerjakan grup `W5-G1`…`W5-G7` (#320–#326) berurutan — detail tiap temuan di `FINDINGS.md` → **Audit ulang wave-5**. (`W4-G1`…`W4-G8` selesai, #310–#318.)

1. ~~#96–#102~~ **merged** on main (EX themes + BS-01). Docs closeout #101 merged; this PR #103 FINDINGS landed.
2. Keep `e2e/` untracked.
3. Optional: `VISUAL_AUDIT_PRESET=exceptions` re-smoke — do not commit PNGs.
4. PO-05 / SHELL-12 / FD-02 only with product call — not mass Wave 2.
