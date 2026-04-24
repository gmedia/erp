---
description: Lanjutkan pekerjaan lintas laptop dari checkpoint terbaru
---

# Workflow: Continue Progress

Gunakan prompt ini saat pindah laptop tetapi tetap memakai Remote SSH server/workspace yang sama.

## 1. Baca Context Wajib

1. `task.md`
2. `.github/copilot-instructions.md`
3. `.claude/rules/agent-rules.md`

## 2. Baseline Cepat (tanpa mengubah kode)

Catatan: command git pada tahap baseline dijalankan langsung di host, sedangkan command runtime project tetap via Sail.

```bash
./vendor/bin/sail php -v
git rev-parse HEAD
git status --short
```

## 3. Sinkronkan Tujuan Sesi

- Ringkas status terakhir dalam 5-10 poin dari `task.md`.
- Identifikasi 1 objective aktif, 1 blocker utama, dan 1 next action terukur.

## 4. Lanjutkan Dalam Wave Kecil

- Kerjakan perubahan bertahap (minimal-risk).
- Jalankan validasi terfokus via Sail (hindari full test jika tidak diminta user).
- Catat command dan hasil validasi yang benar-benar dijalankan.

## 5. Wajib Checkpoint Sebelum Selesai

Update `task.md` dengan format ringkas berikut:

1. `Last updated` (tanggal terbaru)
2. `Current milestone`
3. `What changed in this session`
4. `Validated commands and outcomes`
5. `Open risks/blockers`
6. `Recommended next step`
7. `Continuation Prompt` yang siap copy-paste

## 6. Output Final ke User

- Sebutkan root cause / tujuan yang dikerjakan.
- Sebutkan file yang diubah.
- Sebutkan command validasi yang dijalankan via Sail + hasil.
- Tutup dengan langkah user berikutnya (1-3 item).
