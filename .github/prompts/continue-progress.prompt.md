---
description: "Use when: melanjutkan sesi lintas laptop atau shift dari task.md, checkpoint aktif, dan next action terbaru"
---

# Workflow: Continue Progress

Gunakan prompt ini untuk melanjutkan sesi lintas laptop atau shift pada Remote SSH server/workspace yang sama.

## 1. Mulai dari Sumber Kebenaran Aktif

1. Baca `task.md` terlebih dahulu sebagai source of truth untuk objective aktif, state terakhir, dan next action.
2. Gunakan pembagian dokumen berikut secara konsisten:
	- `task.md` = status handoff aktif
	- `task.changelog.md` = changelog produk/fitur
	- `task.handoff-archive.md` = arsip checkpoint E2E lama
3. Baca `task.handoff-archive.md` hanya jika context aktif di `task.md` belum cukup untuk melanjutkan.
4. Baca `task.changelog.md` hanya jika butuh riwayat perubahan produk/fitur yang relevan.
5. `.github/copilot-instructions.md`
6. Jika ada local workspace override, baca `.kilo/rules/erp-agent-rules.md`
7. Jika objective aktif menyentuh package/framework docs atau refactor struktural, prioritaskan Context7 atau Depwire sesuai routing terbaru di `.github/copilot-instructions.md`

## 2. Verifikasi Baseline Sesi

Catatan: command git pada tahap baseline dijalankan langsung di host, sedangkan command runtime project tetap via Sail.

```bash
git rev-parse HEAD
git status --short
./vendor/bin/sail php -v
```

## 3. Sinkronkan Tujuan Sesi

- Ringkas status aktif dalam 5-10 poin dari `task.md`.
- Abaikan checkpoint historis dari `task.handoff-archive.md` kecuali benar-benar masih relevan dengan objective aktif.
- Tentukan 1 objective aktif, 1 blocker utama, dan 1 next action terukur.
- Tentukan juga MCP utama yang relevan untuk objective aktif: `laravel-boost`, `context7`, atau `depwire`.

## 4. Kerjakan Wave Kecil

- Kerjakan perubahan bertahap (minimal-risk).
- Jalankan validasi terfokus via Sail (hindari full test jika tidak diminta user).
- Catat command dan hasil validasi yang benar-benar dijalankan.

## 5. Perbarui Handoff Aktif

Perbarui `task.md` dengan format ringkas berikut:

1. `Last updated` (tanggal terbaru)
2. `Current milestone`
3. `What changed in this session`
4. `Validated commands and outcomes`
5. `Open risks/blockers`
6. `Recommended next step`
7. `Continuation Prompt` yang siap copy-paste

Jika ringkasan historis lama sudah tidak cocok berada di `task.md`, pindahkan ke `task.handoff-archive.md` daripada menumpuknya di status aktif.

## 6. Output Final ke User

- Sebutkan root cause / tujuan yang dikerjakan.
- Sebutkan file yang diubah.
- Sebutkan command validasi yang dijalankan via Sail + hasil.
- Tutup dengan langkah user berikutnya (1-3 item).
