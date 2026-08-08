# Visual Audit Plan — All Module Registry Pages

**Status:** Planned (not executed)  
**Created:** 2026-08-08  
**Owner handoff:** See root `task.md` (active session) + this doc (full plan)  
**Related:** `docs/module-registry.md`, `database/seeders/MenuSeeder.php`, `resources/js/app-routes.tsx`

> **Agent rule:** On session start / handoff, read **`task.md` first**, then this plan if milestone is visual audit. Do **not** start 85-page capture or parallel visual agents without Wave 0 setup and host memory check.

---

## 1. Goal

For every **navigable** page in `docs/module-registry.md` (aligned with MenuSeeder + SPA routes):

1. Capture controlled **screenshots**
2. Review via **`multimodal-looker`** (what’s wrong in the image) and/or **`visual-engineering`** (how to fix / redesign)
3. Produce a **triaged backlog** of fixes and redesigns (shared shell first, then families, then snowflakes)

**Out of scope for the audit program itself:** full design-system rewrite, forcing all pages into one shell overnight, CI visual regression suite (phase later), dark mode / mobile full matrix (optional later waves).

---

## 2. Inventory (baseline 2026-08-08)

| Source | Count |
|--------|------:|
| Registry slugs | ~87 |
| Navigable routes (`route: /…`) | **~85** |
| Non-navigable / cross-cutting | entity-state-*, approval-history, fiscal-year-auto-select, per-branch-financial-reports, API-only timeline, etc. |
| Existing Playwright screenshot tests | **0** |

### Canonical path corrections (post PR #84)

| Topic | Canonical SPA path |
|-------|-------------------|
| Asset stocktake variances | `/reports/asset-stocktake-variances` |
| Asset dashboard | `/asset-dashboard` |
| Chart of accounts | `/accounts` (permission key is singular `account`, not `accounts` — PR #85) |

**URL source of truth for capture list:** prefer **MenuSeeder leaf `url`s** + `app-routes.tsx`; use registry for family/metadata. Re-diff if registry drifts.

**Local base URL:** `http://127.0.0.1:82/` (`APP_PORT=82`). Health: `GET /up` → 200. Stack often already up — **do not** start a second Vite/`composer dev` on this host without need.

---

## 3. Hard constraints (host + OpenCode)

Host profile (typical): ~8 GiB RAM, 4 CPU, swap in use; free memory often **2–4 GiB**. OpenCode dies if process fan-out is high.

| Rule | Requirement |
|------|-------------|
| Tool calls / turn | Max **3** (prefer 1–2) — `AGENTS.md` |
| Browser workers | **1** only |
| Parallel visual agents | **Forbidden** (no 85× visual-engineering) |
| Review batch size | **3–5 screenshots** per multimodal/visual call |
| Agents per session | ≤ **2** review calls, then write findings & stop |
| Do not | Full Pest suite + full E2E + Vite + many agents together |
| Before browser | `free -h` — prefer **≥ 2.5 GiB available** |
| Optional | Stop non-ERP containers (e.g. orphan ~400 MiB) if OOM risk |

**Capture tooling (pick one pipeline):**

| Option | Use when |
|--------|----------|
| **A.** Opt-in Playwright one-shot (`VISUAL_AUDIT=1`, workers=1) | Default for waves |
| **B.** `/playwright` skill, page-by-page | Debug 1–2 pages |
| **C.** Manual | Not for 85 pages |

**Do not** enable visual audit on default CI until stable and cheap.

---

## 4. Page families (rubric must differ)

| ID | Family | Examples | Shell | Review focus |
|----|--------|----------|-------|--------------|
| F1 | Simple CRUD | departments, positions, branches, categories | `createEntityCrudPage` simple | Table density, search, dialog, empty |
| F2 | Complex CRUD | employees, products, assets list, customers | complex config + siblings | Filters, columns, form length, view modal |
| F3 | Transaction / nested | journals, PO/GR, stock-*, AP/AR, budgets | nested lines, status | Item UX, sticky actions, validation noise |
| F4 | Tree / custom | accounts, asset profile | custom | Hierarchy, breadcrumb |
| F5 | Report DataTable | GL, stock-movement report, budget-variance, … | `ReportDataTablePage` | FY filters, export, scroll |
| F6 | Financial statement | BS, IS, CF, TB, comparative | `FinancialReportPageShell` | FY, numbers, print/export |
| F7 | Dashboard | dashboard, financial, aging, pipeline, asset, stock-monitor | cards + charts | KPI hierarchy, chart legibility |
| F8 | Settings / admin | admin-settings, permissions, users | forms / matrices | Grouping, danger actions |
| F9 | Workflow inbox | my-approvals, posting-journals, depreciation-runs | action-heavy | Primary CTA, chips |
| F10 | Audit trail | pipeline/approval audit | read-only + detail | Scanability |
| F11 | Cross-cutting | fiscal-year-auto-select, per-branch reports | no single URL | **Skip capture**; covered by host pages |

### Priority order (not alphabetical)

1. **P0** — one sample per major family (baseline design debt)  
2. **P1** — money path (AP/AR, journals, PO/GR, budgets, financial/aging dashboards)  
3. **P2** — remaining master / inventory CRUD  
4. **P3** — long-tail reports  
5. **P4** — admin / settings / permissions / audits  

---

## 5. Capture checklist (per route)

| State | Required? | Notes |
|-------|-----------|--------|
| List/default (authenticated, data if available) | **Yes** | Wait list API or tight network idle (≤15s); fail-soft → `capture_failed` in manifest |
| Empty dataset | Optional (later) | Expensive |
| Add/Edit dialog open | Sample per family only | Not 85 forms |
| View modal/detail | Sample complex | |
| Filter expanded | Report family sample | |
| 403 / error | Out of visual scope | Separate audit |

- **Auth:** full-permission user (avoid empty sidebar).  
- **Data:** existing dev seed — no factory storms on weak host.  
- **Viewport wave 1:** **1440×900** only.  
- **Artifact layout:**

```text
docs/visual-audit/
  README.md                 # pointer to this plan + how to run
  FINDINGS.md               # aggregate findings
  BACKLOG.md                # triaged table
  wave-NN/
    manifest.json           # route, status, timestamp, notes
    {slug}__{state}__1440x900.png
```

- **Git:** prefer **gitignore `*.png`** (or external store) unless product wants blobs in repo — decide in Wave 0. Keep markdown findings in git.

---

## 6. Review rubric (score 1–5 + bullets)

1. Hierarchy (title, primary action, chrome vs content)  
2. Density (cram vs sparse)  
3. Alignment (numbers, badges, row actions)  
4. Contrast / light a11y (from pixels)  
5. Consistency with family shell (Shadcn + EntityCrud / Report shells)  
6. Cognitive load (default columns/filters)  
7. CTA clarity  
8. Horizontal overflow risk  
9. Charts/KPI (dashboards)  
10. Dark mode — **out of scope** wave 1 unless broken  

### Required agent output shape

```markdown
## Page / batch
## Severity: P0 | P1 | P2 | P3
## Findings (bullet + evidence)
## Fix type: css-tweak | shared-shell | page-local | redesign-family
## Suggested approach (no code unless implementation requested)
## Do not change (API, permissions, routes)
```

### multimodal-looker vs visual-engineering

| Agent | Role | Input | Output |
|-------|------|-------|--------|
| **multimodal-looker** | Describe issues in screenshots | 3–5 PNGs | Structured findings |
| **visual-engineering** | Fix/redesign plan (load UI skills only on **fix** waves) | Findings + shell paths | Recommendations / later implementation |
| **Main agent** | Capture orchestration, triage, PRs | — | Waves, docs, code |
| **Oracle** | Rare shell architecture conflicts | Text | Decision |

Skills for **implementation** waves only: `ui-styling`, `ui-ux-pro-max`, `design-system` (not every audit call).

---

## 7. Execution waves

### Wave 0 — Setup (do first)

- [ ] `main` up to date (#84 menu/registry, #85 accounts perm merged)  
- [ ] Confirm `http://127.0.0.1:82/up` → 200; **do not** double-start stack  
- [ ] Build canonical URL list (MenuSeeder leaves ∩ app-routes)  
- [ ] Opt-in capture harness (`VISUAL_AUDIT=1`, workers=1)  
- [ ] One-time auth `storageState` (gitignore secrets)  
- [ ] Create `docs/visual-audit/` + FINDINGS/BACKLOG templates; PNG gitignore decision  
- [ ] Smoke: one screenshot `/dashboard`  
- [ ] Memory check before browser  

**Exit:** harness + URL list + smoke PNG + docs skeleton.

### Wave 1 — Baseline shells (5–7 pages)

Suggested set:

| Route | Family |
|-------|--------|
| `/departments` | F1 |
| `/employees` or `/products` | F2 |
| `/purchase-orders` or `/journal-entries` | F3 |
| `/accounts` | F4 |
| `/reports/stock-movement` | F5 |
| `/reports/balance-sheet` | F6 |
| `/financial-dashboard` or `/dashboard` | F7 |

Then: **one** multimodal batch → **one** visual-engineering synthesize → write baseline shared debt.

**Stop gate:** if ≥3 **shared-shell** issues → prefer shell fix PR before Wave 2 mass capture.

### Wave 2 — Money path (~12–15)

AP/AR, invoices/bills, payments/receipts, journals, posting, budgets + variance, aging/financial dashboards (remaining), bank recon, period closing. Batches of 5. Backlog **P0/P1 only**.

### Wave 3 — Master + inventory CRUD (~20)

Warehouses, stock-*, PR/PO/GR/returns, asset list family (not every dialog).

### Wave 4 — Remaining reports (~20)

Rest of `/reports/*`, remaining financial statements, asset reports.

### Wave 5 — Admin / workflow / audit (~10)

users, permissions, admin-settings, pipelines, approvals*, audit trails, my-approvals, pipeline dashboard, stock-monitor, depreciation runs.

### Wave 6 — Optional depth

Dialog samples, mobile **390×844** top 10 P0 only, dark spot-check.

### Wave 7 — Redesign / fix execution (separate from audit)

| Fix type | Branch / PR style |
|----------|-------------------|
| Shared shell | `fix/ui-shell-…` — one PR |
| Family redesign | one PR per family |
| Page snowflake | small `fix/ui-…` PR |

Always: **one task = one branch = one PR** (`AGENTS.md`). Never wait/poll CI.

---

## 8. Backlog format (`docs/visual-audit/BACKLOG.md`)

| ID | Family | Route(s) | Severity | Fix type | Effort | Depends on shell? | Status |
|----|--------|----------|----------|----------|--------|-------------------|--------|

Aggregate **top shared** vs **page-specific**. Prefer one epic doc over 85 GitHub issues.

---

## 9. Definition of done (audit program)

- [ ] ≥95% navigable routes have ≥1 screenshot **or** documented `capture_failed`  
- [ ] `FINDINGS.md` + `BACKLOG.md` triaged P0–P3  
- [ ] At least one shared-shell PR if Wave 1 found repeated patterns  
- [ ] No route/permission regressions from visual work  
- [ ] Host not double-stacked; process budget respected  

**Rough calendar (serial, part-time):** setup + waves 1–5 ≈ **1–2 weeks**; implementation redesign extra.

---

## 10. Risks

| Risk | Mitigation |
|------|------------|
| OOM / OpenCode kill | workers=1, small batches, memory check, no agent swarms |
| 403 / blank SPA | full-perm user; manifest fail |
| Empty data misread as bad UX | note dataset in manifest |
| Missing Vite assets | use existing container build; no second npm dev |
| Route drift | MenuSeeder + app-routes SoT |
| Scope creep | Wave 1 gate; shell before snowflakes |
| Huge PNG commits | gitignore / external |
| CI flake | keep audit off default CI |

---

## 11. Decisions (fill before / during Wave 0)

| # | Question | Default recommendation | Decision |
|---|----------|------------------------|----------|
| 1 | Wave 0+1 only first? | **Yes** | _pending_ |
| 2 | PNG in git? | **gitignore** | _pending_ |
| 3 | Capture login user | seeder super-admin / env | _pending_ |
| 4 | Mobile / dark in program? | **Phase 2 (Wave 6)** | _pending_ |
| 5 | After findings: docs only vs fix P0 shell? | **docs + P0 shell if clear** | _pending_ |

---

## 12. Continuation (for agents)

```
Read task.md (active handoff), then docs/visual-audit-plan.md (this file).
Do NOT capture all 85 pages in one session.
Respect AGENTS.md process budget (≤3 tools/turn, serial).
Host RAM limited: 1 browser worker, batch 3–5 screenshots per review agent.
Next executable step unless task.md says otherwise: Wave 0 setup, then Wave 1 only.
Base URL: http://127.0.0.1:82/ — check /up before browser work.
After #84/#85: variance path /reports/asset-stocktake-variances; no menu perm key "accounts".
Implement fixes only when user asks; default is plan + findings docs.
```

---

## 13. Document map

| File | Role |
|------|------|
| **`task.md`** | **Active** handoff: current wave, branch, blockers, next command |
| **`docs/visual-audit-plan.md`** | Full durable plan (this file) |
| **`docs/visual-audit/FINDINGS.md`** | Session/wave findings (create in Wave 0) |
| **`docs/visual-audit/BACKLOG.md`** | Triaged work items (create in Wave 0) |
| **`docs/module-registry.md`** | Module metadata / test registry |
| **`task.handoff-archive.md`** | Archive old handoffs — do not duplicate long history in `task.md` |

When a visual-audit wave finishes, **update `task.md`** (milestone, what changed, next step, continuation prompt) and append a short delta to FINDINGS/BACKLOG — do not only leave context in chat.
