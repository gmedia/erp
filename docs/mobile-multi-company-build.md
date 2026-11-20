# Mobile Multi-Company Build

## Model

Setiap perusahaan mendapat:

- Web Laravel: `https://<company-slug>.app.dokfin.id`
- Android appId: `com.dokfin.app_<company_slug>` (huruf kecil, non-alfanumerik menjadi `_`)
- Nama aplikasi: nama perusahaan
- Firebase Android app + `google-services.json` (package = appId)
- Backend FCM service account untuk project Firebase perusahaan tersebut

Repo ini menyimpan **template netral**:

- `capacitor.config.ts` default `com.dokfin.erp`
- `android/` template `com.dokfin.erp`
- Tidak ada domain/appId tenant yang di-commit

Konfigurasi perusahaan (URL, appId, nama) diberikan saat build melalui environment/argumen.

## Prasyarat

- Node 22 + `npm install`
- JDK 21
- Android SDK + build-tools
- `google-services.json` per perusahaan
- (opsional) keystore signing

## Build

```bash
scripts/build-company-mobile.sh \
  <company-slug> \
  "<Company Name>" \
  "https://<company-slug>.app.dokfin.id" \
  /path/to/google-services.json
```

Dengan signing release:

```bash
scripts/build-company-mobile.sh \
  <company-slug> \
  "<Company Name>" \
  "https://<company-slug>.app.dokfin.id" \
  /path/to/google-services.json \
  /path/to/release.keystore <store-password> <key-alias> <key-password>
```

Script akan:

1. Menurunkan appId menjadi `com.dokfin.app_<company_slug>`.
2. Memvalidasi `package_name` di `google-services.json` sama dengan appId.
3. Membuat worktree sementara dari `HEAD` (source yang sudah di-commit).
4. Meregenerasi `android/` dengan appId tersebut.
5. Menyalin `google-services.json`, membuat splash/ikon netral.
6. Menjalankan `npm run mobile:sync` dengan `VITE_API_BASE_URL`.
7. Menghasilkan APK debug atau AAB release.

Output:

- `dist-mobile/<company-slug>/app-debug.apk`
- atau `dist-mobile/<company-slug>/app-release.aab`

Catatan: script membangun dari **HEAD**, bukan working tree. Commit perubahan terlebih dahulu.

## Backend per perusahaan

Set environment Laravel:

```dotenv
APP_NAME="<Company Name>"
APP_URL=https://<company-slug>.app.dokfin.id
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

## Verifikasi

- Install APK, login/logout, kamera.
- Aktifkan notifikasi, pastikan token masuk tabel `device_tokens`.
- Kirim test FCM, cek foreground/background/killed.
- Cek `adb logcat` bila registrasi gagal.

## Troubleshooting

- **Package mismatch:** buat ulang `google-services.json` untuk appId yang benar.
- **Token tidak terdaftar:** cek Firebase config, `google-services.json`, dan izin notifikasi.
- **API gagal di WebView:** `VITE_API_BASE_URL` harus terisi; script menulis `.env.mobile` di worktree.
