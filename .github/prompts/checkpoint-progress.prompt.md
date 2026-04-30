---
description: "Use when: menyimpan checkpoint sesi lintas laptop atau shift ke task.md sebelum pause, pindah perangkat, atau selesai kerja"
---

# Workflow: Checkpoint Progress

Gunakan prompt ini untuk menutup sesi lintas laptop atau shift sebelum pause, pindah perangkat, atau selesai kerja.

## 1. Mulai dari Sumber Kebenaran Aktif

1. Baca `task.md` terlebih dahulu sebagai source of truth untuk objective aktif, state terakhir, dan next action.
2. Gunakan pembagian dokumen berikut secara konsisten:
	- `task.md` = status handoff aktif
	- `task.changelog.md` = changelog produk/fitur
	- `task.handoff-archive.md` = arsip checkpoint E2E lama
3. Jangan pindahkan checkpoint teknis E2E ke `task.changelog.md`.
4. Jika sesi aktif memakai MCP penting seperti `laravel-boost`, `context7`, atau `depwire`, catat penggunaannya secara ringkas di checkpoint.

## 2. Verifikasi Baseline Sesi

Catatan: command git pada tahap ini dijalankan langsung di host.

```bash
git rev-parse HEAD
git status --short
./vendor/bin/sail php -v
```

## 3. Perbarui Handoff Aktif

Ringkas checkpoint aktif di `task.md` dengan item berikut:

1. Perubahan utama yang sudah selesai
2. File yang terdampak
3. Commit hash (jika ada)
4. Hal yang belum selesai
5. MCP atau workflow agent penting yang dipakai pada sesi ini

Jika ringkasan historis lama masih penting tetapi tidak lagi aktif, ringkas dan pindahkan ke `task.handoff-archive.md`.

## 4. Simpan Bukti Validasi

- Catat command validasi yang benar-benar dijalankan
- Catat hasilnya: passed/failed + angka durasi singkat
- Bedakan jelas antara hasil chat-run vs user-run

## 5. Tetapkan Next Action Tunggal

Tentukan 1 next action paling prioritas untuk sesi berikutnya.

## 6. Perbarui Continuation Prompt

Perbarui prompt siap pakai di bagian akhir `task.md` agar berisi:

- objective aktif
- constraint user
- baseline command
- command validasi berikutnya
- MCP utama yang kemungkinan dipakai berikutnya bila sudah jelas

## 7. Output Final ke User

- Sebutkan bahwa checkpoint aktif sudah diperbarui.
- Sebutkan file yang diubah.
- Sebutkan command validasi yang benar-benar dijalankan + hasil ringkasnya.
- Tutup dengan 1 next action paling prioritas untuk sesi berikutnya.
