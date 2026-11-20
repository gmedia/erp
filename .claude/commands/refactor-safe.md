---
description: Gunakan saat butuh blast radius, impact analysis, rename/move safety, atau refactor aman lintas modul (berbasis rg + validasi sempit)
---

# Workflow: Refactor Aman

Target: $ARGUMENTS

## 1. Tentukan Anchor

Mulai dari titik perubahan paling dekat:
- file path target
- symbol target
- operasi struktural (`rename`, `move`, `split`, `merge`, `delete`)

## 2. Ambil Konteks Lokal

Baca file target, lalu catat:
- symbol yang didefinisikan file
- `use`/`import` dan export
- file lain yang mengimpor file tersebut:

```bash
rg -n 'ExampleService' app routes database tests
rg -n "from '@/components/example/ExampleForm'" resources/js tests/e2e
```

## 3. Ukur Blast Radius

Cari semua bentuk referensi, bukan hanya nama class:

```bash
rg -n 'ExampleService|example_service|example-service' app routes resources/js tests database config
```

Perhatikan juga referensi tidak langsung:
- string route/endpoint (`/api/examples`), nama permission, query key React Query
- container binding di `app/Providers/`, config, dan factory/seeder
- nama file di test E2E (`tests/e2e/{slug}/`) dan `docs/module-registry.md`

Lakukan ini sebelum:
- mengganti nama class atau function
- mengubah signature public
- memindahkan responsibility ke file lain

## 4. Rencanakan Perubahan Struktural

Untuk `rename`, `move`, `delete`, `split`, atau `merge`, tulis daftar file yang akan disentuh dari hasil langkah 3 sebelum mengedit. Jangan lakukan refactor struktural lintas file sebelum daftar ini lengkap.

## 5. Cek Behavior Package Bila Perlu

Jika perubahan dipicu oleh API package/framework/CLI, baca source versi terpasang di `vendor/` atau `node_modules/` (lihat `composer.lock` / `package-lock.json`), lalu docs resmi untuk versi tersebut.

## 6. Edit Minimum, Lalu Validasi Sempit

- Lakukan perubahan kecil yang langsung menjawab hasil impact analysis
- Validasi dulu pada slice terdekat:
  - `./vendor/bin/sail bin phpstan analyze {path}`
  - `npm run types`
  - `./vendor/bin/sail test --group {slug}`
- Ulangi `rg` dari langkah 3 untuk memastikan tidak ada referensi lama tersisa
- Jika validasi gagal, perbaiki slice yang sama sebelum memperluas scope

## 7. Kapan Workflow Ini Dipakai

- rename atau move file
- split atau merge file besar
- mengganti public API internal
- refactor lintas backend/frontend yang butuh dependency map

## 8. Kapan Tidak Perlu

- typo lokal
- copy change
- styling kecil
- single-file edit tanpa dependency risk
