---
description: "Use when: menguji koordinasi multi-server dari host kedua — verifikasi gh auth, hook, klaim issue, dan pre-push guard sebelum mengerjakan work item lintas host"
---

# Verifikasi Server Kedua (Multi-Host)

Prompt di bawah dipakai dari **host/server kedua** (bukan `dest-2`) untuk membuktikan koordinasi GitHub Issues bekerja lintas host. File ini ikut ter-`pull` lewat `git pull` di `main`.

Sumber checklist: komentar dengan marker `<!-- multi-server-checklist -->` di https://github.com/gmedia/erp/issues/119

## Prompt utama (disarankan)

Salin seluruh blok berikut ke sesi agent (OpenCode/Claude CLI) di server kedua, dari direktori clone repo:

```text
Saya ingin menguji koordinasi multi-server untuk repo gmedia/erp dari HOST KEDUA ini.

Baca dulu:
- AGENTS.md
- https://github.com/gmedia/erp/issues/119 (khususnya komentar dengan marker <!-- multi-server-checklist -->)
- docs/ui-design-rules.md

Ikuti checklist verifikasi server kedua di issue #119, pakai issue kecil #117 (notifikasi email approval) sebagai objek uji. FOKUS ke mekanisme koordinasi, JANGAN mulai mengerjakan fitur penuh.

Lakukan langkah ini sekali, serial (batas ≤3 tool/turn sesuai AGENTS.md):
1. git checkout main && git pull --ff-only
2. gh auth status  → laporkan akun yang terdeteksi. Harus oppytut.
3. git config user.name && git config user.email → kalau kosong, set dari git log -1 --format='%an <%ae>'
4. git config core.hooksPath .githooks lalu verifikasi git config --get core.hooksPath = .githooks
5. Jalankan /claim-issue untuk #117 → cek label status:claimed, assignee, dan hasil gh issue list --assignee @me --repo gmedia/erp
6. Buat branch feat/issue-117-<slug> → cek pre-push guard menerima. Uji juga guard menolak branch tanpa pola (branch sementara, lalu hapus).
7. Jangan buat PR dulu. Berhenti di sini dan laporkan hasilnya.

Laporkan dalam format tabel: langkah | PASS/FAIL | bukti singkat (output perintah).
Kalau ada FAIL, berikan error persisnya dan jangan lanjut ke langkah berikutnya.
Terakhir: tulis komentar ke issue #119 berisi hasil uji dari host ini (nama hostname, akun gh, status tiap langkah).
```

## Versi ringan (lebih cepat)

```text
Dari host kedua ini, uji koordinasi gmedia/erp tanpa mengerjakan fitur.

Baca AGENTS.md + komentar <!-- multi-server-checklist --> di https://github.com/gmedia/erp/issues/119.

Lakukan saja: git pull di main → cek gh auth status (harus oppytut) → cek/set git identity → pasang dan verifikasi core.hooksPath .githooks → klaim issue #117 lewat /claim-issue → verifikasi issue muncul di gh issue list --assignee @me --repo gmedia/erp.

Laporkan PASS/FAIL per langkah + akun gh yang terdeteksi. Jangan buat branch atau PR. Tulis hasilnya sebagai komentar di issue #119.
```

## Catatan

- Prompt utama sengaja **berhenti sebelum PR** supaya uji tidak menghasilkan perubahan kode yang perlu di-merge.
- Kalau semua PASS, lanjutkan dengan mengerjakan #117 penuh (PR `Refs #117`) untuk menguji transisi label `status:in-review → status:done`.
- Kalau FAIL di langkah 2 (akun `gh` beda), itu temuan paling penting — klaim terfragmentasi antar server dan harus disamakan sebelum work item dibagikan.
