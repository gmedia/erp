# AI Handoff Template (GitHub Issue comment)

> Tempel sebagai komentar issue `gmedia/erp` dengan marker `<!-- checkpoint -->`.
> Tidak lagi ditulis ke `task.md` (deprecated sejak 2026-11-17).

```markdown
<!-- checkpoint -->
**Last updated:** YYYY-MM-DD HH:MM UTC
**Issue:** #N
**Server:** <hostname> (tmux: <session>)

## Goal
Objective aktif dalam 1-2 kalimat.

## Current Milestone
Status milestone terbaru + angka hasil penting.

## User Constraints
- Constraint 1
- Constraint 2

## What Changed In This Session
- Perubahan 1
- Perubahan 2

## Files Touched
- path/file-1
- path/file-2

## Latest Validations
- `sail test --group x` -> passed (12s)
- `npm run types` -> passed

## Open Risks / Blockers
- Risiko 1

## Recommended Next Step
1. Langkah paling prioritas untuk server berikutnya.
```

## Continuation Prompt (isi di komentar, bukan file)

```text
Lanjutkan issue #N di gmedia/erp. Baca komentar terakhir dengan marker <!-- checkpoint -->.
Jalankan /continue-progress. Jangan baca task.md sebagai sumber aktif.
Constraints: ...
First steps: 1) ... 2) ...
```
