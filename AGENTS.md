# AGENT WORKFLOW

> Rules the agent MUST follow without exception. Violation = invalid session.

## Core Rules

1. **NEVER wait for CI/CD.** Push and move on.
2. **One task = one branch** (`feat/*` or `fix/*`) = **one MR/PR.**
3. **Every MR must have a handoff** (what was done, files changed, next steps).
4. **Session start: process open MRs first** — merge if green, fix if red.
5. **Process budget (OpenCode stability) — HARD LIMIT.** Parallel tool/agent fan-out kills the host session. Follow **Tool & Process Concurrency** below with zero exceptions.

## Tool & Process Concurrency (HARD — prevents OpenCode kill)

> OpenCode has repeatedly been killed when the agent fired many tools/subagents in one turn. Prefer **serial** work. Throughput is secondary to session survival.

### Hard limits (per assistant turn)

| Kind | Max per turn | Notes |
|------|--------------|-------|
| Total tool calls | **3** | Prefer **1–2**. Never “spray” reads/greps. |
| Background `task` / explore / librarian | **0 default** | Use only if user explicitly asks for parallel research. Max **1** if used. |
| `run_in_background=true` | **Avoid** | Prefer sync, single agent. |
| Simultaneous file reads | **1–2** | Do not batch 5–10 `Read` calls. |
| Simultaneous bash / git / gh | **1** | Chain with `&&` in one shell if sequential steps needed. |
| Sonar / MCP / Depwire / Context7 | **1** | One MCP call, then reason; next call only if needed. |

### Required workflow

1. **One file at a time** for edits: read → edit → next file. Never parallel multi-file edit batches.
2. **One search at a time**: one `grep`/`glob`/`bash`, use results, then next search.
3. **No parallel subagent swarms** for “explore everything”. Main agent greps/reads directly for small scopes.
4. **After any kill / interrupt**: resume with **strict serial** mode (1 tool per step) until the task finishes.
5. **When in doubt**: fewer tools. Incomplete progress + alive session > full plan + dead OpenCode.

### Explicitly forbidden

- ❌ 5+ tool calls in a single assistant message
- ❌ Multiple `task(subagent_type="explore"|"librarian")` in parallel
- ❌ Parallel `Read` of many files “to save time”
- ❌ Parallel Sonar + git + file reads + explore agents together
- ❌ Spawning agents for work the main agent can do with one `grep`/`Read`

### Allowed exceptions (still keep total ≤ 3 tools)

- Two independent **tiny** reads of known paths (e.g. two line ranges already identified)
- One `git status`/`log` combined in a **single** bash script
- User explicitly orders parallel research (“fire explore agents”)

### Post-kill recovery (MANDATORY)

1. After OpenCode kill / interrupt: **strict serial** — **1 tool call per assistant turn** until the interrupted task is finished or stable.
2. Do **not** re-fire explore/librarian/oracle swarms to “catch up”.
3. Resume from `task.md` + `git status` only; re-apply unfinished edits **one file at a time**.
4. Prefer completing a small shippable slice (commit/PR) over re-planning the whole theme.

### Why this exists

Host RAM/CPU is limited. Parallel `Read`/`Edit`/`task` batches have **killed OpenCode mid-T5** (and earlier sessions). Session survival > agent throughput.

## Workflow

```
1. rtk git checkout -b feat/<desc>   # from latest main
2. Incremental commits                # feat:, fix:, refactor:, test:
3. rtk git push && gh pr create       # with handoff description
4. Move to next task immediately      # DO NOT wait for CI
```

> Use `rtk` wrapper for all git operations. `rtk` ensures the correct environment and SSH keys are configured. Never use raw `git` directly — always `rtk git <command>`.

## Session Start Checklist

```bash
gh pr list --state open --assignee @me    # Find your pending MRs
gh pr checks <id>                          # Check CI status
# CI green → squash merge → delete branch → pull main
gh pr merge <id> --squash --delete-branch && rtk git pull --ff-only
# CI red   → checkout branch → fix → push
gh pr view <id> --json headRefName         # Get branch name
```

## Commit Prefixes

| Prefix | When |
|--------|------|
| `feat:` | New feature or behavior |
| `fix:` | Bug fix |
| `refactor:` | Code restructure, no behavior change |
| `test:` | Test additions or fixes |
| `docs:` | Documentation only |
| `chore:` | Build, CI, dependencies |

## MR Handoff Template

```
## What was done
- [brief bullet list of changes]

## Files changed
- `path/to/file` — [what and why]

## Verification
- [ ] sail test --group {slug}
- [ ] sail bin duster fix
- [ ] npm run types
- [ ] CI passing (gh pr checks)

## Next steps
- [optional follow-up]
```

## Anti-Patterns

- ❌ Working directly on `main`
- ❌ Polling CI or waiting for pipelines
- ❌ Multiple unrelated changes in one MR
- ❌ Empty MR descriptions
- ❌ Force-push or rebase shared branches
- ❌ Using raw `git` directly — always use `rtk git`
- ❌ **Multi-process fan-out** (many tools/agents in one turn) — kills OpenCode; see **Tool & Process Concurrency**

## Quality Gate

- **WAJIB** gunakan SonarQube MCP tools (`sonarqube_*`) untuk SEMUA masalah terkait quality gate.
- Tools yang tersedia: `sonarqube_get_project_quality_gate_status`, `sonarqube_search_sonar_issues_in_projects`, `sonarqube_get_component_measures`, `sonarqube_search_duplicated_files`, `sonarqube_search_files_by_coverage`, `sonarqube_get_duplications`, `sonarqube_get_file_coverage_details`, dll.
- **Alur standar**:
  1. Cek status quality gate: `sonarqube_get_project_quality_gate_status(projectKey="gmedia_erp")`
  2. Jika failed → ambil metrik inti: `sonarqube_get_component_measures(projectKey="gmedia_erp", metricKeys=["duplicated_lines", "duplicated_blocks", "duplicated_lines_density", "ncloc", "coverage", "bugs", "vulnerabilities", "code_smells"])`
  3. Tentukan prioritas berdasarkan metrik yang paling kritis.
  4. Untuk duplikasi: `sonarqube_search_duplicated_files(projectKey="gmedia_erp")` → ambil file prioritas tertinggi.
  5. Untuk coverage: `sonarqube_search_files_by_coverage(projectKey="gmedia_erp", maxCoverage=50)` → tambah test.
  6. Untuk issues: `sonarqube_search_sonar_issues_in_projects(projects=["gmedia_erp"], severities=["HIGH","BLOCKER"])` → fix critical issues.
- **JANGAN** berspekulasi tentang status quality gate tanpa data dari SonarQube MCP.

---

# PROJECT KNOWLEDGE BASE

**Generated:** 2025-05-02
**Commit:** ddc886a7
**Branch:** main

## OVERVIEW

ERP system (gmedia) — Laravel 12 JSON API backend + React 19 full SPA frontend. Manages finance, assets, inventory, purchasing, approvals, and pipelines across 60+ modules.

## STRUCTURE

```
erp/
├── app/                # Backend: Actions, Domain, DTOs, Exports, Http, Models, Services
├── resources/js/       # Frontend SPA: React + TypeScript + Shadcn UI + TanStack Query
├── routes/api/         # 63 modular route files (auto-included by routes/api.php)
├── database/           # Migrations (67), Factories (67), Seeders (24)
├── tests/              # Pest (Feature/Unit) + Playwright E2E
├── docs/               # Design docs, module registry, refactor progress
├── .github/            # Skills, prompts, workflows, agent configs
├── scripts/            # Sync scripts, E2E lock helper
├── docker/             # Docker configs
└── .kilo/              # Kilo sync configs
```

## WHERE TO LOOK

| Task | Location | Notes |
|------|----------|-------|
| Add new module (backend) | `app/Actions/{Module}/`, `app/Http/Controllers/`, `routes/api/` | Follow `docs/development-patterns.md` |
| Add new module (frontend) | `resources/js/pages/{slug}/`, `resources/js/components/{slug}/` | Use `createEntityCrudPage` pattern |
| Register route (frontend) | `resources/js/app-routes.tsx` | Lazy-loaded `<Route>` |
| Register route (backend) | `routes/api/{module-slug}.php` | Auto-included |
| Add export | `app/Exports/` | Declarative `columns()` pattern |
| Add report | `app/Actions/Reports/`, `resources/js/pages/reports/` | `ReportDataTablePage` shell |
| Shared UI components | `resources/js/components/common/`, `resources/js/components/ui/` | Shadcn + custom |
| Shared backend traits | `app/Actions/Concerns/`, `app/Http/Controllers/Concerns/` | `InteractsWithExportFilters`, `LoadsResourceRelations` |
| Entity configs | `resources/js/utils/entityConfigs.ts` | Simple vs Complex config |
| Form schemas | `resources/js/utils/schemas.ts` (or per-module) | Zod validation |
| Type definitions | `resources/js/types/` | 36 type files |
| Hooks | `resources/js/hooks/` | `useEntityForm`, `useCrudQuery`, `useCrudMutations` |
| User guide content | `docs/user-guide-*.md` | Markdown files served via `/api/user-guide` |
| User guide page | `resources/js/pages/user-guide/index.tsx` | Renders markdown from API |
| Module metadata | `docs/module-registry.md` | Full E2E + Pest registry |
| Dev patterns | `docs/development-patterns.md` | Canonical implementation guide |
| Session handoff | `task.md` | Active status + next action |
| Skills/templates | `.github/skills/` | Scaffolding for new features |

## CONVENTIONS

- **NO Inertia.js** — pure API + SPA. Never import `@inertiajs/react`.
- **Sanctum Bearer Token** — stateless auth, not session/cookies.
- **`routes/web.php`** — catch-all SPA only. NEVER add routes here.
- **Sail required** — all runtime commands via `./vendor/bin/sail <cmd>`.
- **Empty wrapper classes** — must have `// Intentionally empty...` comment body.
- **Import hygiene** — always `use` at top, never FQCN with `\` in body.
- **Duster fix** — run `sail bin duster fix` after PHP changes.
- **Module naming** — kebab-case for routes/slugs, PascalCase for classes.
- **Form prop** — always `entity` (never module-specific name).
- **Test groups** — `->group('{module-slug}')` kebab-case, single group only.
- **react-helmet-async** — aliased to local shim (`resources/js/lib/`), never re-add upstream dep.

## ANTI-PATTERNS (THIS PROJECT)

- ❌ `Inertia::render()`, `@inertiajs/react` imports
- ❌ `actingAs($user)` in tests → use `Sanctum::actingAs($user)`
- ❌ `assertInertia()` → use `assertJson()`, `assertJsonStructure()`
- ❌ Routes in `routes/web.php`
- ❌ FQCN with leading `\` in PHP body code
- ❌ Empty class bodies without intent comment
- ❌ Redundant `'created_at' => 'datetime'` casts in models
- ❌ Running commands without `sail` prefix

## ARCHITECTURE PATTERNS

| Pattern | Backend | Frontend |
|---------|---------|----------|
| Simple CRUD | `SimpleCrud*` base classes | `createSimpleEntityConfig()` |
| Complex CRUD | Custom Request/Resource/Export | `createComplexEntityConfig()` + sibling files |
| Reports | `AbstractReportIndexExport` + Action | `ReportDataTablePage` |
| Transactions | `StoresItemsInTransaction` trait | `ItemFormDialog` + nested form |
| Exports | `columns()` declarative + `InteractsWithExportFilters` | Export button in toolbar |
| Pipelines | `EntityState` engine | `EntityStateActions` component |
| Approvals | `ApprovalFlow` + `ApprovalFlowStep` | `MyApprovals` inbox |

## COMMANDS

```bash
# Development
composer dev                          # Server + queue + pail + vite (concurrent)
sail npm run dev                      # Vite only

# Testing
sail test --group {module-slug}       # Pest by module
sail test                             # All Pest tests
npm run test:e2e                      # All Playwright E2E
npx playwright test tests/e2e/{slug}/ # E2E by module

# Quality
sail bin phpstan analyze              # Static analysis
sail bin duster fix                   # Code style (TLint + Pint + CS Fixer)
sail npm run lint                     # ESLint
sail npm run format                   # Prettier
npm run types                         # TypeScript check

# Utilities
sail artisan ide-helper:generate      # Facade autocomplete
sail artisan ide-helper:models -RW    # Model PHPDoc
```

## NOTES

- **11,983 files** total (1,594 PHP, 377 TSX, 194 TS) — large codebase.
- **63 API route files** — one per module, auto-included.
- **68 models**, **67 migrations**, **67 factories** — near 1:1 ratio.
- **SonarCloud** integrated — quality gate, duplication, coverage tracked.
- **Sentry** for error monitoring.
- **Octane** for performance (production).
- **Scramble** for auto-generated API docs.
- **Zustand** used alongside React Query for client state.
- **`task.md`** is the handoff document — read it first when continuing work.
- **`.github/skills/`** has scaffolding templates for new modules.
