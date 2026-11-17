---
description: "Use when: sesi opencode baru di server mana pun — klaim issue GitHub sebelum membuat branch, dan update status saat PR dibuka"
---

# Workflow: Claim Issue (multi-server)

Pakai prompt ini di **setiap server opencode** sebelum mengerjakan task. Issue = satu klaim = satu branch = satu PR. `task.md` adalah handoff **lokal per server**, jangan dipakai sebagai koordinasi lintas server.

## 1. Cek MR/PR terbuka lebih dulu (aturan AGENTS.md)

```bash
gh pr list --state open --assignee @me --repo gmedia/erp
```

Kalau ada yang CI merah → kerjakan dulu. Hijau → squash merge + delete branch, lalu lanjut.

## 2. Pilih & klaim issue

```bash
gh issue list --repo gmedia/erp --label 'status:available' --limit 20
```

Sebelum klaim, pastikan:
- label masih `status:available`
- tidak ada komentar `<!-- claim -->` aktif (baca: `gh issue view N --repo gmedia/erp --comments`)

Lalu klaim (atomic-ish: label dulu, komentar setelahnya):

```bash
gh issue edit N --repo gmedia/erp \
  --remove-label 'status:available' --add-label 'status:claimed' \
  --add-assignee @me
gh issue comment N --repo gmedia/erp --body "$(cat <<'EOF'
<!-- claim -->
Server: <hostname> (tmux: <session>)
Branch: <feat|fix>/issue-N-slug
Claimed: <YYYY-MM-DD HH:MM UTC>
ETA: <estimasi>
EOF
)"
```

Kalau server mati >24 jam tanpa update, opencode lain boleh `unclaim`: komentar alasan → hapus `status:claimed` → kembalikan `status:available`.

## 3. Buat branch / worktree

Satu server, satu issue aktif:

```bash
git checkout main && git pull --ff-only
git checkout -b feat/issue-N-slug
```

Kalau lebih dari satu opencode di host yang sama, pakai worktree agar tidak tabrak checkout:

```bash
git worktree add /home/tut/erp-wt-N -b feat/issue-N-slug main
```

## 4. Kerjakan & buka PR

- Ikuti konvensi `.github/copilot-instructions.md` dan `AGENTS.md` (≤3 tool/turn, serial, Sail, Sanctum, dll).
- PR description wajib memuat `Closes #N` dan template handoff (What was done / Files changed / Verification / Next steps).
- Setelah `gh pr create`, workflow `issue-lifecycle.yml` otomatis mengganti label ke `status:in-review`. Saat PR di-merge → `status:done`.

```bash
gh pr create --repo gmedia/erp --title "feat: ... (#N)" --body "Closes #N\n\n## What was done\n..."
```

## 5. Jangan pernah

- ❌ Kerjakan issue yang masih `status:available` tanpa klaim
- ❌ Satu branch berisi dua issue
- ❌ Tulis status kerja lintas server ke `task.md` repo (lokal saja)
- ❌ Commit `.env`, `e2e/`, atau artifact hasil deploy
- ❌ Tunggu CI (AGENTS.md: push & move on)
