---
name: Session Handoff
description: Standarisasi checkpoint lintas server/shift ke GitHub Issues agar sesi berikutnya langsung lanjut tanpa konflik antar opencode.
---

# Session Handoff Skill

Gunakan skill ini saat beberapa server menjalankan opencode pada workspace yang sama, atau user sering berpindah server/laptop.

> **Sejak 2026-11-17:** sumber kebenaran aktif = **https://github.com/gmedia/erp/issues**, bukan `task.md` (deprecated). Lihat `/claim-issue`, `/continue-progress`, `/checkpoint-progress`.

## Kapan Dipakai

- User memakai banyak server dengan tmux + opencode.
- Task panjang (refactor, stabilisasi test, rollout bertahap).
- Ada risiko dua server mengerjakan hal yang sama atau kehilangan konteks.

## Tujuan

1. Satu issue = satu klaim = satu branch = satu PR.
2. Agent berikutnya bisa lanjut dari komentar `<!-- checkpoint -->` tanpa re-triage.
3. Tidak ada server yang menimpa status server lain lewat file bersama.

## Sumber Kebenaran

- **Aktif:** issue di `gmedia/erp` — label `status:*`, komentar `<!-- claim -->` dan `<!-- checkpoint -->`.
- **Arsip (baca hanya jika perlu):** `task.md`, `task.changelog.md`, `task.handoff-archive.md` — deprecated, jangan ditulis.
- Referensi teknis tetap di repo: `.github/copilot-instructions.md`, `AGENTS.md`, `docs/development-patterns.md`, `docs/module-registry.md`.
- Prompt reusable:
  - `.github/prompts/claim-issue.prompt.md`
  - `.github/prompts/continue-progress.prompt.md`
  - `.github/prompts/checkpoint-progress.prompt.md`

## Workflow Wajib

### A. Saat Memulai Sesi Baru

1. `gh pr list --state open --assignee @me --repo gmedia/erp` — selesaikan PR merah dulu.
2. `gh issue list --repo gmedia/erp --assignee @me --state open` — pilih yang berlabel `status:claimed`. Fallback bila kombinasi filter kosong: `gh search issues --repo gmedia/erp --assignee @me --label 'status:claimed' --state open`.
3. Kalau kosong → jalankan `/claim-issue` sebelum mengerjakan apa pun.
4. Baca `gh issue view N --repo gmedia/erp --comments` → cari `<!-- checkpoint -->` terakhir. Fallback: `gh api repos/gmedia/erp/issues/N/comments --jq '.[] | .body'`.
5. Baseline cepat:

```bash
git rev-parse HEAD
git status --short
./vendor/bin/sail php -v
```

6. Ambil 1 objective + 1 next action dari checkpoint.

### B. Saat Bekerja

1. Eksekusi dalam wave kecil.
2. Simpan command validasi yang benar-benar dijalankan.
3. Hindari full-suite bila ada constraint targeted validation only.

### C. Sebelum Menutup Sesi

Jalankan `/checkpoint-progress`. Tulis komentar issue berisi:

1. `Last updated`
2. `Current milestone`
3. `What changed in this session`
4. `Validated commands and outcomes`
5. `Open risks/blockers`
6. `Recommended next step`

**Jangan** menulis ke `task.md` — deprecated dan bersama antar server.

## Template

- `.github/skills/session-handoff/resources/handoff.template.md` — format komentar issue.

## Definition of Done

- Sesi berikutnya dapat dimulai dengan: `/continue-progress` + `gh issue view N --comments` (fallback: `gh api repos/gmedia/erp/issues/N/comments --jq '.[] | .body'`).
- Tidak ada ambiguity tentang issue aktif, objective, hasil validasi terakhir, dan next action.
- Tidak ada server yang menimpa handoff server lain.
