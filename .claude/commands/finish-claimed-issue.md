---
description: "Use when: melanjutkan work item yang sudah diklaim di host ini dari branch lokal sampai PR Refs #N dan verifikasi transisi label — menutup uji langkah 7 multi-server"
---

# Lanjutkan Issue yang Sudah Diklaim (host eksekusi)

Prompt ini dipakai di host **tempat issue diklaim** (mis. `<host>` untuk #117). Ia melanjutkan pekerjaan dari branch lokal yang sudah ada sampai PR dan verifikasi label. File ini ikut ter-`pull` lewat `git pull` di `main`.

Prasyarat: langkah 1–6 checklist multi-server sudah PASS (lihat `.claude/commands/verify-second-server.md` dan komentar `<!-- multi-server-checklist -->` di issue #119).

## Prompt utama

Salin blok berikut ke sesi agent (OpenCode/Claude CLI) di host yang sudah mengklaim issue:

```text
Lanjutkan issue #117 (notifikasi email approval) dari host ini sampai selesai. Issue ini sudah diklaim di host ini pada langkah sebelumnya (label status:claimed, assignee oppytut).

Baca dulu:
- AGENTS.md
- docs/ui-design-rules.md (cek apakah aturan UI relevan; untuk #117 kemungkinan tidak ada UI baru — sebutkan jika demikian)
- .claude/commands/continue-progress.md dan checkpoint-progress.prompt.md
- Komentar issue #117 via: gh api repos/gmedia/erp/issues/117/comments --jq '.[] | .body'
  (gh issue view --comments mungkin gagal di host ini karena deprecation GraphQL Projects classic — sudah ada fallback REST)

Kerjakan serial, ≤3 tool/turn, sesuai AGENTS.md:
1. Verifikasi branch aktif masih feat/issue-117-email-notification-approval. Kalau belum, checkout branch itu. Jangan buat branch baru.
2. Baca issue #117 untuk objective + DoD. Ringkas 1 objective + 1 next action.
3. Implementasi fitur notifikasi email approval sesuai scope issue:
   - Mail template + event listener saat ApprovalRequest dibuat
   - Queue job (QUEUE_CONNECTION=database)
   - Pest test dengan Mail::fake()
   - Ikuti konvensi backend di docs/development-patterns.md (Actions, Sanctum, duster)
4. Validasi: sail test --group yang relevan, sail bin duster fix, lalu cek git status.
5. Tulis checkpoint ke issue #117 via /checkpoint-progress (komentar marker <!-- checkpoint -->).
6. Commit dengan prefix feat:, push branch.
7. Buka PR dengan: Refs #117 (BUKAN Closes #117) + handoff template (What was done / Files changed / Verification / Next steps).
8. VERIFIKASI TRANSISI LABEL (ini yang ingin dibuktikan):
   - Tunggu beberapa detik setelah gh pr create
   - Cek: gh issue view 117 --repo gmedia/erp --json state,labels --jq '{state,labels:[.labels[].name]}'
   - Harapan: state OPEN, label berubah jadi status:in-review (bukan claimed)
   - Setelah PR di-merge (squash + delete branch), cek lagi: label jadi status:done, issue tetap OPEN
9. Update tabel "Klaim aktif" di issue #119: tandai #117 selesai, hapus baris server.

Laporkan: commit hash, PR URL, status label sebelum/sesudah merge, dan apakah langkah 8 PASS.
Kalau ada FAIL di transisi label, jangan hapus apa pun — laporkan error persisnya ke komentar issue #119.
```

## Versi ringkas (kalau sudah yakin mekanismenya)

```text
Lanjutkan #117 di host ini: kerjakan notifikasi email approval, commit feat:, push, buka PR dengan Refs #117 (bukan Closes), lalu verifikasi label issue berubah claimed → in-review → done dan issue tetap OPEN. Update tabel klaim di #119. Baca fallback REST untuk komentar di .claude/commands/continue-progress.md bila gh issue view --comments gagal.
```

## Catatan

- Jangan pakai `Closes #117` — itu akan menutup issue dan mengosongkan papan koordinasi.
- Kalau `gh issue view --comments` gagal, pakai `gh api repos/gmedia/erp/issues/117/comments --jq '.[] | .body'`.
- Setelah langkah 8 PASS, uji multi-server dinyatakan lengkap: 7/7 langkah berhasil dari host berbeda.
