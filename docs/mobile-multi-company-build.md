# Mobile Multi-Company Build

## Model

Setiap perusahaan mendapat:

- Web Laravel: `https://<company-slug>.app.perusahaan.contoh`
- Android appId: `<MOBILE_APP_ID_PREFIX>.app_<company_slug>` (huruf kecil, non-alfanumerik menjadi `_`)
- Nama aplikasi: nama perusahaan
- Firebase Android app + `google-services.json` (package = appId)
- Backend FCM service account untuk project Firebase perusahaan tersebut

Repo ini menyimpan **template netral**:

- `capacitor.config.ts` default `com.example.erp`
- `android/` template `com.example.erp`
- Tidak ada domain/appId tenant yang di-commit

Konfigurasi perusahaan (awalan appId, URL, nama) diberikan saat build melalui environment/argumen.

## Awalan appId (`MOBILE_APP_ID_PREFIX`) — WAJIB

`MOBILE_APP_ID_PREFIX` wajib diisi setiap build. Tidak ada default; skrip gagal bila kosong. Format: huruf kecil, angka, underscore, dipisah titik, tanpa spasi (contoh `com.perusahaan.contoh`).

### Perusahaan yang sudah terpasang

Perusahaan yang build Android-nya sudah pernah terbit/terpasang **wajib** memakai awalan yang sama dengan build sebelumnya. Ambil nilainya dari build terakhir (mis. dari `applicationId` APK yang sudah terpasang atau dari `google-services.json` lama), jangan menebak.

Alasan: ID aplikasi Android bersifat permanen. Bila berubah, Android menganggapnya aplikasi baru, pengguna harus memasang ulang, dan notifikasi Firebase harus didaftarkan ulang.

## Prasyarat

- Node 22 + `npm install`
- JDK 21
- Android SDK + build-tools
- `google-services.json` per perusahaan
- (opsional) keystore signing

## Build

```bash
MOBILE_APP_ID_PREFIX=<prefix> scripts/build-company-mobile.sh \
  <company-slug> \
  "<Company Name>" \
  "https://<company-slug>.app.perusahaan.contoh" \
  /path/to/google-services.json
```

Dengan signing release (wajib menyertakan versi):

```bash
MOBILE_APP_ID_PREFIX=<prefix> \
MOBILE_VERSION_NAME=1.4.0 \
MOBILE_VERSION_CODE=14 \
scripts/build-company-mobile.sh \
  <company-slug> \
  "<Company Name>" \
  "https://<company-slug>.app.perusahaan.contoh" \
  /path/to/google-services.json \
  /path/to/release.keystore <store-password> <key-alias> <key-password>
```

Versi juga boleh diberikan sebagai opsi: `--version-name=1.4.0 --version-code=14`.

Script akan:

1. Menurunkan appId menjadi `${MOBILE_APP_ID_PREFIX}.app_<company_slug>`.
2. Memvalidasi `package_name` di `google-services.json` sama dengan appId.
3. Memvalidasi versi: wajib pada mode release, `versionCode` integer dan harus lebih besar dari
   `release.json` sebelumnya di `dist-mobile/<company-slug>/`.
4. Membuat worktree sementara dari `HEAD` (source yang sudah di-commit).
5. Meregenerasi `android/` dengan appId tersebut, lalu menulis `versionName`/`versionCode`.
6. Menyalin `google-services.json`, membuat splash/ikon netral.
7. Menjalankan `npm run mobile:sync` dengan `VITE_API_BASE_URL`, `VITE_APP_VERSION`,
   dan `VITE_APP_VERSION_CODE`.
8. Menghasilkan APK debug, atau AAB + APK release bertanda tangan.

Output mode release:

- `dist-mobile/<company-slug>/app-release.apk` — APK release bertanda tangan
- `dist-mobile/<company-slug>/app-release.apk.sha256` — checksum SHA-256
- `dist-mobile/<company-slug>/app-release.aab` — untuk Play/MDM
- `dist-mobile/<company-slug>/release.json` — `version_name`, `version_code`, `sha256`,
  `size_bytes`, `app_id` (dipakai halaman unduh rilis)

Output mode debug:

- `dist-mobile/<company-slug>/app-debug.apk`

Validasi tanpa build (tidak butuh JDK/Android SDK): tambahkan `--dry-run`.

Catatan: script membangun dari **HEAD**, bukan working tree. Commit perubahan terlebih dahulu.

## Backend per perusahaan

Set environment Laravel:

```dotenv
APP_NAME="<Company Name>"
APP_URL=https://<company-slug>.app.perusahaan.contoh
PUSH_DRIVER=fcm
FCM_PROJECT_ID=<firebase-project-id>
FCM_CREDENTIALS=/path/inside/container/service-account.json
```

Pastikan:

- Kode `main` terbaru sudah ter-deploy (device-token API, CORS, FCM sender).
- CORS mengizinkan `http://localhost` dan `capacitor://localhost`.
- `google-services.json` dan service account **tidak** di-commit.

## Firebase: per project atau shared?

- **Per perusahaan (disarankan):** isolasi kredensial; setiap backend memakai service account project masing-masing.
- **Shared project:** satu service account dapat mengirim ke banyak app; `google-services.json` tetap berbeda per package.

Putuskan model ini sebelum onboarding perusahaan kedua.

## CI

- Simpan `google-services.json` dan keystore sebagai secret per perusahaan.
- Gunakan matrix build atau manual dispatch dengan input slug.
- Jangan pernah commit kredensial atau domain tenant.

## Signing & distribusi

- Debug APK untuk UAT internal.
- Release AAB untuk Play/MDM.
- Gunakan Play App Signing atau keystore sendiri.
- Package name menjadi permanen; jika memakai managed Google Play private, package name terkunci private.

## Distribusi privat (APK dari server sendiri)

Untuk perusahaan yang **tidak** memakai Play Store, aplikasi didistribusikan sebagai APK release
bertanda tangan yang diunduh dari server ERP perusahaan itu sendiri. Halaman unduh dan admin rilis
dikerjakan pada bagian 2 issue; panduan pengguna ada di `docs/user-guide-mobile-install.md`.

### Keystore per perusahaan

- Satu keystore per perusahaan (appId), dipakai untuk **seluruh** rilis berikutnya.
- Simpan keystore **di luar repo** (password manager / vault / backup terenkripsi). Jangan pernah
  commit keystore, password, atau `google-services.json`.
- **Keystore hilang = tidak bisa memperbarui aplikasi yang sudah terpasang.** Android menolak APK
  dengan tanda tangan berbeda; pengguna harus mencopot pemasangan (data lokal hilang) sebelum
  memasang build baru. Buat cadangan dan uji restore sebelum rilis pertama.
- Ganti keystore hanya dengan konsekuensi di atas; jangan membuat keystore baru untuk sekadar
  merapikan nama berkas.
- Simpan juga `MOBILE_APP_ID_PREFIX` perusahaan; appId bersifat permanen.

### Versi wajib naik

- Mode release gagal bila `MOBILE_VERSION_NAME`/`MOBILE_VERSION_CODE` kosong.
- `versionCode` harus **integer dan selalu naik** antar rilis. Skrip membandingkan dengan
  `dist-mobile/<company-slug>/release.json` dan menolak nilai yang sama atau lebih kecil.
- `versionName` hanya label yang terlihat pengguna; `versionCode` yang menentukan Android mau
  memasang pembaruan di atas versi lama.
- Simpan `release.json` bersama APK agar server dapat menampilkan versi dan checksum yang benar.

### Verifikasi checksum

Setelah mengunduh dari server ERP, cocokkan SHA-256 dengan `release.json` atau berkas checksum:

```bash
sha256sum -c app-release.apk.sha256
# atau bandingkan nilai sha256 di release.json
sha256sum app-release.apk
```

Checksum hanya membuktikan berkas tidak berubah saat diunduh; checksum yang dipublikasikan lewat
kanal yang sama tidak melindungi dari server yang disusupi. Batasi akses halaman unduh ke pengguna
yang berhak.

### Izin "instal dari sumber tidak dikenal"

Android memblokir pemasangan APK di luar Play Store secara default. Pengguna perlu mengizinkan
aplikasi browser/berkas yang dipakai untuk memasang APK:

1. Buka **Setelan → Aplikasi → Akses khusus → Instal aplikasi tidak dikenal** (nama menu berbeda
   per versi Android).
2. Pilih browser atau aplikasi berkas yang dipakai mengunduh, lalu aktifkan izinnya.
3. Setelah pemasangan selesai, izin boleh dimatikan kembali.

Play Protect dapat menampilkan peringatan untuk APK di luar Play Store; jelaskan kepada pengguna
bahwa sumbernya server ERP perusahaan, bukan toko aplikasi.

### Memperbarui aplikasi

- Unduh APK versi baru dari server ERP, lalu pasang seperti biasa. Selama appId dan keystore sama
  serta `versionCode` lebih besar, Android memperbarui tanpa menghapus data pengguna.
- Jangan menurunkan `versionCode`; Android menolak pemasangan versi lebih rendah di atas versi
  terpasang. Untuk rollback, terbitkan build baru dengan `versionCode` lebih tinggi.

### Pencabutan akses

APK yang sudah terpasang tidak bisa "ditarik" dari perangkat, jadi cabut akses di sisi server:

- Cabut token Sanctum pengguna (logout paksa) atau nonaktifkan akunnya.
- Perangkat yang hilang/dicuri: cabut token perangkat tersebut dan hapus baris `device_tokens` yang
  terkait agar tidak menerima notifikasi.
- Bila perusahaan berhenti memakai aplikasi, nonaktifkan user terkait dan hentikan publikasi APK
  di halaman unduh.

## Verifikasi

- Install APK, login/logout, kamera.
- Aktifkan notifikasi, pastikan token masuk tabel `device_tokens`.
- Kirim test FCM, cek foreground/background/killed.
- Cek `adb logcat` bila registrasi gagal.

## Troubleshooting

- **Package mismatch:** buat ulang `google-services.json` untuk appId yang benar.
- **Token tidak terdaftar:** cek Firebase config, `google-services.json`, dan izin notifikasi.
- **API gagal di WebView:** `VITE_API_BASE_URL` harus terisi; script menulis `.env.mobile` di worktree.
