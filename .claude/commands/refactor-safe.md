---
description: Gunakan saat butuh Depwire untuk blast radius, impact analysis, rename/move safety, atau refactor aman lintas modul
---

# Workflow: Refactor Aman dengan Depwire

## 1. Tentukan Anchor

Mulai dari titik perubahan paling dekat:
- file path target
- symbol target
- operasi struktural (`rename`, `move`, `split`, `merge`, `delete`)

## 2. Ambil Konteks Lokal

```text
mcp_depwire_get_file_context(filePath: "app/Services/ExampleService.php")
```

Gunakan hasil ini untuk melihat:
- symbol yang didefinisikan file
- import dan export
- file lain yang mengimpor file tersebut

## 3. Ukur Blast Radius

```text
mcp_depwire_impact_analysis(symbol: "ExampleService", file: "app/Services/ExampleService.php")
```

Gunakan ini sebelum:
- mengganti nama class atau function
- mengubah signature public
- memindahkan responsibility ke file lain

## 4. Simulasikan Perubahan Struktural

```text
mcp_depwire_simulate_change(operation: "rename", target: "app/Services/ExampleService.php", destination: "app/Services/NewExampleService.php")
```

Pilih operasi yang sesuai:
- `rename`
- `move`
- `delete`
- `split`
- `merge`

Jangan lakukan refactor struktural lintas file sebelum hasil simulasi dipahami.

## 5. Ambil Docs Eksternal Bila Perlu

Jika perubahan dipicu oleh API package/framework/CLI, pakai Context7 sebelum edit:

```text
mcp_context7_resolve-library-id(libraryName: "React Router", query: "route object API migration")
mcp_context7_query-docs(libraryId: "/remix-run/react-router", query: "route object API migration")
```

## 6. Edit Minimum, Lalu Validasi Sempit

- Lakukan perubahan kecil yang langsung menjawab hasil impact analysis
- Validasi dulu pada test atau compile slice yang paling dekat
- Jika validasi gagal, perbaiki slice yang sama sebelum memperluas scope

## 7. Kapan Prompt Ini Dipakai

- rename atau move file
- split atau merge file besar
- mengganti public API internal
- refactor lintas backend/frontend yang butuh dependency map

## 8. Kapan Tidak Perlu

- typo lokal
- copy change
- styling kecil
- single-file edit tanpa dependency risk