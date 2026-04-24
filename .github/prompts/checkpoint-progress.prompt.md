---
description: Simpan checkpoint sesi agar bisa dilanjutkan dengan cepat
---

# Workflow: Checkpoint Progress

Gunakan prompt ini sebelum berhenti kerja, terutama saat akan pindah laptop/shift.

## 1. Verifikasi State Repo

Catatan: command git pada tahap ini dijalankan langsung di host.

```bash
git rev-parse --short HEAD
git status --short
```

## 2. Ringkas Perubahan Sesi

Wajib tulis di `task.md`:

1. Perubahan utama yang sudah selesai
2. File yang terdampak
3. Commit hash (jika ada)
4. Hal yang belum selesai

## 3. Simpan Bukti Validasi

- Tulis command validasi yang benar-benar dijalankan
- Tulis hasilnya: passed/failed + angka durasi singkat
- Bedakan jelas antara hasil chat-run vs user-run

## 4. Tetapkan Next Action Tunggal

Tentukan hanya 1 next action paling prioritas untuk sesi berikutnya.

## 5. Perbarui Continuation Prompt

Tambahkan prompt siap pakai di bagian akhir `task.md` yang berisi:

- objective aktif
- constraint user
- baseline command
- command validasi berikutnya
