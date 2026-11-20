---
description: "Use when: mengerjakan work item UI baru (mis. #115 upload dokumen Asset Movement) sambil membuktikan docs/ui-design-rules.md benar-benar dipakai agent"
---

# Implementasi UI dengan Uji Pemakaian Aturan

Prompt ini dipakai saat agent akan membuat/mengubah UI. Selain menyelesaikan fitur, agent **wajib melaporkan aturan UI mana yang dipakai** — ini menguji apakah `docs/ui-design-rules.md` benar-benar menjadi panduan, bukan sekadar file yang ada.

Contoh objek: issue #115 (upload dokumen pendukung Asset Movement).

## Prompt utama

```text
Kerjakan issue #115 (upload dokumen pada Asset Movement) dari host ini sampai PR. Fokus: fitur + pembuktian bahwa aturan UI dipakai.

Baca dulu, SEBELUM menulis kode:
1. .github/copilot-instructions.md + AGENTS.md
2. docs/ui-design-rules.md — khususnya Bagian 2 (pembuatan UI) dan Bagian 3 (checklist review)
3. resources/js/AGENTS.md
4. File terkait:
   - resources/js/components/asset-movements/AssetMovementForm.tsx
   - resources/js/components/asset-movements/AssetMovementViewModal.tsx
   - resources/js/utils/schemas.ts (assetMovementFormSchema)
   - satu pola upload existing: resources/js/components/common/ImportDialog.tsx
   - docs/user-guide-asset-movements.md
5. Issue #115 via gh api repos/gmedia/erp/issues/115/comments --jq '.[] | .body'
   (fallback REST; gh issue view --comments bisa gagal)

Lakukan serial, ≤3 tool/turn:
1. Verifikasi branch (klaim dulu dengan /claim-issue bila belum).
2. Tulis 1 paragraf ringkas di komentar issue #115 SEBELUM coding, berisi:
   - Aturan UI Bagian 2 mana yang relevan (sebut ID/nomor, mis. 2.6 Form, 2.7 Modal, 2.5 warna)
   - Komponen shared mana yang dipakai (EntityForm / InputField / dll.)
   - Salah satu anti-pattern yang DIHINDARI
   Ini bukti bahwa dokumen dibaca.
3. Implementasi:
   - Field upload di AssetMovementForm (create/edit), prop tetap bernama entity
   - Validasi Zod ditambah di assetMovementFormSchema
   - Storage disk + validasi tipe file sesuai konvensi project
   - Backend: request validation + penyimpanan file (ikuti docs/development-patterns.md)
   - Tampilkan dokumen di AssetMovementViewModal via ViewField
4. Terapkan checklist Bagian 3 secara singkat: cek toolbar tidak berubah, modal tetap memo()+ViewModalShell, data-testid E2E tidak berubah tanpa update spec.
5. Validasi: sail test --group asset-movements, npm run types, duster fix.
6. /checkpoint-progress → komentar <!-- checkpoint --> di #115, sertakan ringkasan "aturan UI yang dipakai".
7. Commit feat:, push, buka PR dengan Refs #115 (BUKAN Closes) + handoff.

Laporkan di akhir: pr URL, aturan UI yang dipakai, dan apakah langkah 2 dilakukan sebelum coding.
```

## Versi ringkas

```text
Kerjakan #115. Sebelum coding, baca docs/ui-design-rules.md Bagian 2, lalu tulis di komentar issue aturan mana yang relevan + komponen shared yang dipakai. Implementasi upload dokumen Asset Movement (form Zod, storage, ViewModal), validasi test/types/duster, lalu PR Refs #115 dengan handoff. Sertakan bukti pemakaian aturan UI di checkpoint.
```

## Catatan

- Kalau agent **tidak** menyebut aturan UI sama sekali dalam laporan, itu temuan: dokumen belum efektif dan perlu dipindah ke tempat yang lebih dekat dengan agent.
- Jangan mengubah `data-testid` tanpa update spec E2E — aturan Bagian 2.4.
- `Refs #115` menjaga issue tetap OPEN dengan label `status:done` setelah merge.
