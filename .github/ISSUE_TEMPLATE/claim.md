---
name: Work item (opencode claim)
about: Satu unit kerja yang bisa diklaim oleh satu server opencode
title: "[SCOPE] short description"
labels: status:available
assignees: ''
---

## Goal
<!-- Apa hasil akhir yang diharapkan. Satu issue = satu branch = satu PR. -->

## Scope
<!-- Modul/file terdampak. Contoh: resources/js/pages/foo, routes/api/foo.php -->

## Why / context
<!-- Tautan ke docs, screenshot finding, atau product call. -->

## Definition of Done
- [ ] Branch `feat/issue-N-slug` atau `fix/issue-N-slug` dari `main`
- [ ] `Closes #N` di deskripsi PR
- [ ] Verifikasi sesuai modul: `sail test --group {slug}`, `npm run types`, `sail bin duster fix`
- [ ] PR punya handoff (What was done / Files changed / Verification / Next steps)
- [ ] Label di-update ke `status:in-review`

---

## ClaimProtocol

> Salin komentar ini saat mengambil issue. Satu issue hanya boleh diklaim satu server.

```markdown
<!-- claim -->
Server: <hostname> (tmux: <session>)
Branch: <feat|fix>/issue-N-slug
Claimed: <YYYY-MM-DD HH:MM UTC>
ETA: <estimasi>
```

 Sebelum klaim: pastikan label masih `status:available` dan tidak ada komentar `<!-- claim -->` aktif. Lalu ganti label ke `status:claimed` **dan assign ke diri sendiri** (`gh issue edit N --add-assignee @me`) agar muncul di `gh issue list --assignee @me`.

Melepas klaim: komentar alasan, hapus label `status:claimed`, hapus assignee, kembalikan ke `status:available`. Kalau server mati >24 jam tanpa update, opencode lain boleh mengambil alih setelah komentar `unclaim`.

<!-- ⚠️ Jangan tulis status kerja di task.md repo — file itu lokal per server. Pakai komentar issue ini sebagai handoff lintas server. -->
