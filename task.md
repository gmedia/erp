# task.md — Active Session Handoff

**Last updated:** 2026-08-06  
**Current milestone:** Sonar MAJOR wave 2 + budget E2E status docs shipped as PR #79  
**Branch:** `fix/sonar-major-wave2` @ `5bc1c0d7`  
**Open PRs:** #77 (CI running, exclusive Employee*/BR/number-format), #79 (wave2 — do not poll CI)

## What changed in this session

- Combined #1+#2 on `fix/sonar-major-wave2` from main tip `a46fef52` (#78)
- php:S103: aging dashboard action + DetectCrossBranchJournals + 3 Backfill* commands
- S9011: explicit `type="button"` on shared UI, AccountTree chevron, verify-email
- S4782: `calendar.tsx` `buttonVariant` → `NonNullable<...>`
- `AGENTS.md`: Tool & Process Concurrency hard limits (max 3 tools/turn, no parallel explore swarms)
- `IMPLEMENTATION_STATUS.md`: Budget #72 CRUD + #75 variance report E2E wording
- 6 atomic commits; pushed; **PR #79** opened

## Commits on branch

```
5bc1c0d7 docs: mark budget variance report E2E as landed (#75)
a3e0a79e docs: add OpenCode process budget concurrency rules
4d13cb7b fix: tighten calendar buttonVariant type (S4782)
49553691 fix: add explicit button types on AccountTree and verify-email
ea1ab741 fix: add explicit button types on shared UI (S9011)
1fee38a2 fix: break long PHP lines (Sonar S103)
```

## Validated commands and outcomes

- Multi-commit via git-master (`GIT_MASTER=1`) — clean tree after 6 commits
- `git push -u origin HEAD` OK
- `gh pr create` → https://github.com/gmedia/erp/pull/79
- **Did not** poll CI (AGENTS rule)

## Open risks/blockers

- #77 exclusive files — do not edit until #77 merged
- OpenCode process budget — stay serial (≤3 tools/turn)
- Stash `task-md-pre-wave2` may still exist; drop after handoff confirmed

## Recommended next step

1. **User-driven:** when #77 green → squash-merge + delete branch + `rtk git pull --ff-only` on main
2. **User-driven:** when #79 green → same
3. **Parallel-safe while CI runs (if needed):** next Sonar MAJOR batch that does **not** touch #77 or #79 file sets — only after checking exclusive lists; prefer wait for merge to reduce conflict risk
4. Do **not** poll `gh pr checks` in a loop

## Continuation Prompt

```
Read task.md. PR #79 is Sonar wave 2 + docs (branch fix/sonar-major-wave2).
PR #77 still exclusive until merged. Session start: process open MRs first
(merge green, fix red). Never wait/poll CI. Stay within AGENTS.md process budget
(max 3 tools/turn, serial). Do not re-touch #77 files.
```

## Exclusive file sets (do not touch while open)

**#77:** EmployeeViewModal, IndexEmployeesAction, EmployeeExport, IndexEmployeeRequest, BankReconciliationWorkspace, number-format.ts  
**#79 (this PR):** wave2 PHP S103 targets, S9011 TSX list, calendar.tsx, AGENTS.md, IMPLEMENTATION_STATUS.md
