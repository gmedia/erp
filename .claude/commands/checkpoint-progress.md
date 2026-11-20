---
description: "Use when: menyimpan checkpoint sesi ke komentar issue gmedia/erp sebelum pause, pindah server, atau selesai kerja"
---

# Workflow: Checkpoint Progress (Issues)

Pakai prompt ini untuk menutup sesi sebelum pause / pindah server. **Checkpoint ditulis sebagai komentar issue**, bukan ke `task.md` (deprecated).

## 1. Tentukan issue aktif

```bash
gh issue list --repo gmedia/erp --assignee @me --state open
# Pilih yang berlabel status:claimed (fallback: gh search issues --repo gmedia/erp --assignee @me --label 'status:claimed' --state open)
```

Catat nomor issue `N`. Kalau lebih dari satu, checkpoint masing-masing secara terpisah. Jangan tulis ke `task.md`.

## 2. Verifikasi Baseline Sesi

Command git dijalankan langsung di host; command runtime tetap via Sail.

```bash
git rev-parse HEAD
git status --short
./vendor/bin/sail php -v
```

## 3. Susun Checkpoint

Ringkas poin berikut:

1. Perubahan utama yang sudah selesai
2. File yang terdampak
3. Commit hash (jika ada)
4. Hal yang belum selesai / blocker
5. Skill/command agent penting yang dipakai sesi ini
6. Validasi: command yang benar-benar dijalankan + hasil (passed/failed, durasi singkat)
7. Next action tunggal paling prioritas

## 4. Tulis ke Issue sebagai komentar

```bash
gh issue comment N --repo gmedia/erp --body "$(cat <<'EOF'
<!-- checkpoint -->
**Last updated:** <YYYY-MM-DD HH:MM UTC>
**Server:** <hostname> (tmux: <session>)

### What changed
- ...

### Files changed
- `path` — ...

### Validated commands and outcomes
- `sail test --group x` — passed (12s)
- `npm run types` — passed

### Open risks / blockers
- ...

### Recommended next step
1. ...
EOF
)"
```

Marker `<!-- checkpoint -->` wajib — dipakai `/continue-progress` untuk mencari handoff terakhir. Komentar terakhir menang; tidak perlu menghapus yang lama (riwayat terjaga).

## 5. Tutup sesi

- Kalau PR sudah dibuka: pastikan deskripsi PR memuat `Refs #N` (bukan `Closes #N` — agar issue tidak auto-close; workflow `issue-lifecycle.yml` akan mengubah label ke `status:in-review`, lalu `status:done` saat merge).
- Kalau belum: biarkan label `status:claimed` tetap ada.

## 6. Output Final ke User

- Sebutkan issue `#N` tempat checkpoint disimpan.
- Sebutkan file yang diubah.
- Sebutkan command validasi + hasil ringkas.
- Tutup dengan 1 next action paling prioritas.
