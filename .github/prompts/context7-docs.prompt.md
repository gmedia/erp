---
description: Gunakan saat butuh lookup docs package, framework, SDK, API, atau CLI yang terbaru melalui Context7
---

# Workflow: Lookup Docs dengan Context7

## 1. Tentukan Library dan Tujuan

Pastikan pertanyaan menyebut:
- nama library atau package
- versi jika ada
- hal spesifik yang dicari: syntax, migration, config, API behavior, atau example

Jika package termasuk Laravel ecosystem yang sudah tercakup docs project, pertimbangkan `mcp_laravel-boost_search-docs(...)` lebih dulu. Untuk package umum atau non-Laravel, gunakan Context7.

## 2. Resolve Library ID Sekali

```text
mcp_context7_resolve-library-id(libraryName: "React Router", query: "route object API migration")
```

Pilih hasil yang paling relevan berdasarkan:
- nama resmi library
- reputasi source
- jumlah snippet
- kecocokan versi

## 3. Query Docs dengan Pertanyaan Spesifik

```text
mcp_context7_query-docs(libraryId: "/remix-run/react-router", query: "route object API migration from v6 to v7")
```

Gunakan query yang sempit:
- sebut nama API atau fitur
- sebut versi atau konteks migrasi jika ada
- hindari kata kunci generik seperti `routing` atau `auth`

## 4. Batasi Pemakaian

- Hindari lebih dari 3 panggilan Context7 per pertanyaan
- Reuse `libraryId` yang sudah ditemukan
- Naikkan presisi query, bukan jumlah query, jika hasil masih kabur

## 5. Kembalikan Jawaban yang Actionable

Saat merangkum hasil:
- tulis syntax atau config yang disarankan
- sebut breaking change atau caveat penting
- kaitkan ke file repo yang terdampak jika task-nya implementasi

## 6. Kapan Prompt Ini Dipakai

- upgrade dependency
- integrasi library baru
- debugging syntax/config package
- migrasi API CLI atau SDK

## 7. Kapan Tidak Perlu

- logic bisnis internal repo
- refactor lokal tanpa dependency eksternal
- pencarian dependensi antar file, karena itu pekerjaan Depwire