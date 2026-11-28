# Docs index

Index for agents and developers. Prefer these paths over inventing new status docs.

## Active (keep current)

| Doc | Role |
|-----|------|
| [development-patterns.md](./development-patterns.md) | Canonical implementation patterns (CRUD, reports, FY auto-select) |
| [ui-design-rules.md](./ui-design-rules.md) | Aturan UI: page shell, toolbar, tabel, warna status, form, modal |
| [api-error-codes.md](./api-error-codes.md) | Kode error API dan format respons |
| [module-registry.md](./module-registry.md) | E2E + Pest registry — **counts = table/YAML rows** |
| [database/IMPLEMENTATION_STATUS.md](./database/IMPLEMENTATION_STATUS.md) | Implementation status SSOT |
| [refactor-sonar-progress.md](./refactor-sonar-progress.md) | **FROZEN** Sonar batch A–G history |
| [prompt-recommendations.md](./prompt-recommendations.md) | Template prompt untuk agent (create/refactor/test) |
| `user-guide-*.md` | End-user guides served via `/api/user-guide` |
| [user-guide-mobile-install.md](./user-guide-mobile-install.md) | Pasang & perbarui APK Android |
| [user-guide-mobile-app.md](./user-guide-mobile-app.md) | Rilis APK, unduh, verifikasi SHA-256, versi minimum |
| [user-guide-users.md](./user-guide-users.md) | Akun pengguna yang terhubung dengan karyawan |
| [user-guide-permissions.md](./user-guide-permissions.md) | Izin akses per karyawan |
| [user-guide-admin-settings.md](./user-guide-admin-settings.md) | Perusahaan, regional, SMTP, autentikasi, akuntansi |
| [user-guide-approvals.md](./user-guide-approvals.md) | Alur, delegasi, pemantauan, dan jejak audit persetujuan |
| [user-guide-pipeline-dashboard.md](./user-guide-pipeline-dashboard.md) | Dasbor sebaran status dan entitas stale |
| [user-guide-pipeline-audit-trail.md](./user-guide-pipeline-audit-trail.md) | Riwayat perpindahan status pipeline |

## Mobile

| Doc | Role |
|-----|------|
| [mobile-multi-company-build.md](./mobile-multi-company-build.md) | Build per perusahaan & distribusi APK privat |
| [mobile-e2e.md](./mobile-e2e.md) | Prasyarat & langkah E2E Android emulator (Capacitor) |
| [capacitor-mobile-implementation-plan.md](./capacitor-mobile-implementation-plan.md) | Rencana implementasi Capacitor |

## Social login

| Doc | Role |
|-----|------|
| [social-login-design.md](./social-login-design.md) | Desain social login |
| [social-login-deployment.md](./social-login-deployment.md) | Langkah deployment social login |

## Dashboards (do not confuse)

| Surface | Route | Guide |
|---------|-------|-------|
| Home / main | `/dashboard` | [user-guide-dashboard.md](./user-guide-dashboard.md) — entity totals only |
| Financial | `/financial-dashboard` | [user-guide-financial-dashboard.md](./user-guide-financial-dashboard.md) — KPI / YoY |
| Aging AR/AP | `/aging-dashboard` | [user-guide-aging-dashboard.md](./user-guide-aging-dashboard.md) |
| Asset | `/asset-dashboard` | [user-guide-asset-dashboard.md](./user-guide-asset-dashboard.md) |
| Pipeline | `/pipeline-dashboard` | [user-guide-pipeline-dashboard.md](./user-guide-pipeline-dashboard.md) |

## Design docs

| Doc | Status |
|-----|--------|
| [budget-management-design.md](./budget-management-design.md) | **Implemented** (2026-06-22); Pest OK; E2E #72 (CRUD) + #75 (variance report) |
| [vehicle-loan-design.md](./vehicle-loan-design.md) | **Implemented** — modul `vehicle-loans` (lihat #568, #569) |
| [profit-loss-by-department-design.md](./profit-loss-by-department-design.md) | Fase 1–2 merged (PR #546, #549); fase 3 **blocked** (#114, #563) |
| [visual-audit-plan.md](./visual-audit-plan.md) | **In progress** — wave berjalan, lihat [visual-audit/FINDINGS.md](./visual-audit/FINDINGS.md) & [BACKLOG.md](./visual-audit/BACKLOG.md) |
| [database/\*_design.md](./database/) | Module design specs (see IMPLEMENTATION_STATUS) |

## Catatan upgrade

| Doc | Role |
|-----|------|
| [upgrade-aging-as-of-journal-discount.md](./upgrade-aging-as-of-journal-discount.md) | Aging as-of, jurnal, dan diskon |
| [upgrade-asset-disposal-journal.md](./upgrade-asset-disposal-journal.md) | Jurnal pelepasan aset |
| [upgrade-group-account-posting.md](./upgrade-group-account-posting.md) | Posting akun grup |
| [upgrade-inventory-average-cost-in-transit.md](./upgrade-inventory-average-cost-in-transit.md) | Average cost & barang dalam perjalanan |
| [upgrade-journal-failure-rollback.md](./upgrade-journal-failure-rollback.md) | Rollback kegagalan jurnal |
| [upgrade-period-lock.md](./upgrade-period-lock.md) | Kunci periode |
| [upgrade-permission-route-alignment.md](./upgrade-permission-route-alignment.md) | Keselarasan permission & route |
| [upgrade-posted-document-cancellation.md](./upgrade-posted-document-cancellation.md) | Pembatalan dokumen terposting |
| [upgrade-purchasing-stock-integrity.md](./upgrade-purchasing-stock-integrity.md) | Integritas stok pembelian |

## Archive

- [archive/](./archive/) — historical refactor, SPA migration, Sonar progress archive

## Handoff (repo root) — deprecated

Active coordination since 2026-11-17 lives in **https://github.com/gmedia/erp/issues** (`status:*` labels, `<!-- claim -->` / `<!-- checkpoint -->` comments). The files below are historical archives only.

- `task.md` — deprecated session handoff
- `task.changelog.md` — product changelog
- `task.handoff-archive.md` — archived E2E handoffs
