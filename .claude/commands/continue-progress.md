---
description: "Use when: melanjutkan sesi lintas server dari issue yang sedang diklaim di gmedia/erp, bukan dari task.md"
---

# Workflow: Continue Progress (Issues)

Pakai prompt ini untuk melanjutkan kerja di server mana pun. **Sumber kebenaran aktif = GitHub Issues**, bukan `task.md` (deprecated sejak 2026-11-17).

## 1. Cek MR/PR terbuka lebih dulu (aturan AGENTS.md)

```bash
gh pr list --state open --assignee @me --repo gmedia/erp
```

Kalau ada yang CI merah → kerjakan dulu. Hijau → squash merge + delete branch, lalu lanjut.

## 2. Ambil objective aktif dari issue yang diklaim

```bash
gh issue list --repo gmedia/erp --assignee @me --state open --limit 20

# Pilih yang berlabel status:claimed dari hasil di atas.
# Catatan: kombinasi --assignee + --label kadang kosong saat propagasi GitHub;
# filter label manual lebih reliabel. Fallback: gh search issues --repo gmedia/erp --assignee @me --label 'status:claimed' --state open
```

- Kalau **tidak ada** yang diklaim: jangan mulai kerja. Jalankan `/claim-issue` dulu untuk mengambil issue `status:available`.
- Kalau ada **lebih dari satu**: pilih yang paling lama diklaim, atau tanya user.
- Baca detail + riwayat handoff terakhir:

```bash
gh issue view N --repo gmedia/erp --comments
# Fallback bila gh issue view gagal (deprecation GraphQL Projects classic):
gh api repos/gmedia/erp/issues/N/comments --jq '.[] | .body'
```

Cari komentar terakhir berisi `<!-- checkpoint -->` atau `<!-- claim -->`. Itu pengganti "Continuation Prompt" dari `task.md` lama.

## 3. Referensi pendukung (tetap di repo)

File berikut **masih** dibaca dari repo — bukan dipindah ke Issues:

- `AGENTS.md` — aturan arsitektur, anti-pattern, workflow, batas ≤3 tool/turn, Sail
- `docs/development-patterns.md` — pola CRUD/report/export/FY
- `docs/module-registry.md` — metadata test per modul
- `task.md` — **arsip status lama saja**, jangan dijadikan sumber aktif

## 4. Verifikasi Baseline Sesi

```bash
rtk git rev-parse HEAD
rtk git status --short
./vendor/bin/sail php -v
```

## 5. Sinkronkan Tujuan Sesi

- Ringkas status dari issue + komentar checkpoint (5–10 poin).
- Tentukan 1 objective aktif, 1 blocker, 1 next action terukur.
- Tentukan skill/command utama yang relevan (lihat `.claude/README.md`).

## 6. Kerjakan Wave Kecil

- Perubahan bertahap, minimal-risk.
- Validasi terfokus via Sail (hindari full suite bila tidak diminta).
- Catat command + hasilnya untuk ditulis ke checkpoint issue.

## 7. Simpan Handoff ke Issue (bukan task.md)

Sebelum menutup sesi, jalankan `/checkpoint-progress`. Ringkasan ditulis sebagai komentar issue dengan marker `<!-- checkpoint -->`, berisi:

1. `Last updated`
2. `Current milestone`
3. `What changed in this session`
4. `Validated commands and outcomes`
5. `Open risks/blockers`
6. `Recommended next step`

Jangan tulis ke `task.md` — file itu deprecated dan bersama antar server.

## 8. Output Final ke User

- Sebutkan issue `#N` yang dikerjakan.
- Sebutkan root cause / tujuan.
- Sebutkan file yang diubah.
- Sebutkan command validasi via Sail + hasil.
- Tutup dengan 1–3 langkah berikutnya.
