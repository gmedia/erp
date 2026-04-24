---
name: Session Handoff
description: Standarisasi checkpoint progress lintas laptop/shift agar sesi berikutnya langsung lanjut.
---

# Session Handoff Skill

Gunakan skill ini saat user sering berpindah laptop/shift, tetapi tetap memakai Remote SSH workspace yang sama.

## Kapan Dipakai

- User bilang sering pindah laptop/shift.
- Task panjang (refactor, stabilisasi test, rollout bertahap).
- Ada risiko kehilangan konteks antar sesi.

## Tujuan

1. Progress tersimpan konsisten di repo.
2. Agent berikutnya bisa lanjut tanpa re-triage besar.
3. User cukup jalankan prompt lanjutan.

## Sumber Kebenaran

- `task.md` adalah single source of truth untuk status aktif.
- Prompt reusable:
  - `.github/prompts/continue-progress.prompt.md`
  - `.github/prompts/checkpoint-progress.prompt.md`

## Workflow Wajib

### A. Saat Memulai Sesi Baru

1. Baca `task.md` dulu.
2. Jalankan baseline cepat:

Catatan: command git pada baseline dijalankan di host, command runtime project tetap via Sail.

```bash
git rev-parse HEAD
git status --short
./vendor/bin/sail php -v
```

3. Ambil 1 objective aktif + 1 next action dari `task.md`.

### B. Saat Bekerja

1. Eksekusi dalam wave kecil.
2. Simpan command validasi yang benar-benar dijalankan.
3. Hindari full-suite bila ada constraint user untuk targeted validation only.

### C. Sebelum Menutup Sesi

Update `task.md` minimal bagian berikut:

1. `Last updated`
2. `Current milestone`
3. `What changed in this session`
4. `Validated commands and outcomes`
5. `Open risks/blockers`
6. `Recommended next step`
7. `Continuation Prompt`

## Template

Gunakan template ini saat perlu merapikan struktur handoff:

- `.github/skills/session-handoff/resources/handoff.template.md`

## Definition of Done

- Sesi berikutnya dapat dimulai cukup dengan:
  - membaca `task.md`
  - memakai prompt `/continue-progress`
- Tidak ada ambiguity tentang objective aktif, hasil validasi terakhir, dan next action.
