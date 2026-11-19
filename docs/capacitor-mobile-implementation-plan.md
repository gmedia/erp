# Rancangan Implementasi Mobile — Capacitor JS (Android-first)

> **Status:** FINAL v3.5 — Android-first (Fase 0/1/3/5 + FCM sender)  
> **Tanggal:** 2026-11-19  
> **Lingkup:** ERP on-premise multi-cabang — Laravel 12 JSON API + React 19/TypeScript/Vite 7 SPA  
> **Keputusan induk:** Android-first dengan Capacitor v8. iOS **ditunda** sebagai backlog aditif (tanpa rewrite). PWA dipakai sebagai jembatan interim untuk pengguna iPhone dan desktop.

Dokumen ini adalah **rencana**, bukan izin untuk langsung mengubah kode. Setiap fase wajib menghasilkan satu MR/PR yang bisa di-ship (`Refs #N`), bukan satu big-bang merge. Perubahan v3 → v3.1 diringkas di §13.

> **Prinsip Android-first:** semua keputusan desain dirancang **platform-agnostik** agar iOS nanti bersifat aditif — backend akan memakai `device_tokens` dengan `enum('android','ios')`, modul `token-storage.ts` akan dual-path, dan abstraksi kamera akan punya cabang web/native. **Saat ini belum ada satupun** (lihat §2). Yang ditunda hanyalah konfigurasi platform, APNs, Xcode, dan distribusi Apple.

---

## 1. Tujuan dan Definition of Done (Android)

### Tujuan (fase Android)

Pengguna ERP di Android bisa:

1. Menginstal aplikasi dari **Play Store / managed Google Play / APK+MDM** sebagai native-shell Capacitor.
2. Menerima **push notification (FCM)** yang reliable (approval request, overdue vehicle loan, pipeline transition).
3. Mengambil foto **langsung dari kamera** (selfie pickup/return vehicle loan, foto dokumen asset movement) tanpa file-picker generik.
4. Tetap bisa dipakai di browser desktop tanpa regresi.

### Definition of Done (Android)

- [ ] Aplikasi React yang sama berjalan di WebView Capacitor Android tanpa rewrite UI.
- [ ] `takePhoto()` native di Android; file-picker tetap tersedia di web.
- [ ] Push token terdaftar per user per device; notifikasi **dengan payload notification** terkirim saat app foreground, background, dan killed di Android. Push data-only yang tidak menampilkan notifikasi **tidak** termasuk DoD (lihat OD-3).
- [ ] Sanctum token tidak lagi tersimpan di `localStorage` saat berjalan di native Android — **di semua 8 pemanggil / 4 file** (lihat §5 Fase 5).
- [ ] Semua test lulus: Pest (group baru), Playwright E2E existing (satu instance lewat lock, `workers` default 1), TypeScript, Duster, dan SonarQube quality gate.
- [ ] Tidak ada perubahan perilaku pada 61 action module bisnis. Satu-satunya perubahan di layer notifikasi adalah seleksi channel (§5 Fase 1).
- [ ] Tidak ada brand hardcoded (AGENTS.md Rule 0).

### Di luar DoD Android (backlog iOS — §12)

Install iOS, push APNs, kamera iOS, Keychain, dan distribusi Apple **belum** termasuk DoD v3. Pengguna iPhone tetap bisa membuka ERP via browser (Safari) untuk approval; bila Fase 6 dijalankan, mereka mendapat PWA interim (install + pengalaman lebih baik) sampai backlog iOS dikerjakan. Bila Fase 6 dilewati, akses web biasa tetap tersedia.

---

## 2. Baseline kode — fakta terverifikasi

| Aspek | Kondisi saat ini | Bukti |
|---|---|---|
| Frontend | React 19 + TS + Vite 7, 63 direktori halaman, `createEntityCrudPage` dipakai 43 halaman | `resources/js/AGENTS.md` |
| Auth | Sanctum Bearer token di `localStorage('api_token')` — **8 pemanggil di 4 file** | `resources/js/contexts/auth-context.tsx:119,155,168,181`; `resources/js/lib/axios.ts:11,23`; `resources/js/components/delete-user.tsx:35`; `resources/js/pages/user-guide/index.tsx:32` |
| Notifikasi | `NotificationService` dengan channel `database` + `mail`; `array_intersect($event->channels(), $configured)` di baris 30; dedupe `(event_key, identity, user, channel)` | `app/Services/NotificationService.php:21-70`; `config/notifications.php:16-27` (default approval event = `['database','mail']`); `app/Notifiables/ApprovalEvent.php:34-37`; `app/Notifiables/VehicleLoanDueEvent.php:32-35` |
| Model User | **Belum** punya relasi `deviceTokens()` (hanya `socialAccounts()` di :93) | `app/Models/User.php` |
| Kamera | `<input type="file" accept="image/jpeg,image/png">` di vehicle-loans pickup/return | `resources/js/pages/vehicle-loans/pickup/index.tsx:148-158`, `.../return/index.tsx:127,142` |
| PWA / push / Capacitor | **Tidak ada** service worker, manifest, Web Push, FCM, APNs, atau Capacitor | grep atas codebase → 0 match |
| Routing API | `routes/api.php` **explicit `require`** tiap file modul di dalam group `auth:sanctum`; tidak ada auto-discovery | `routes/api.php:23,31-97`; `routes/AGENTS.md` |
| Controller | Modul **flat** di `app/Http/Controllers/`; `Api/` hanya `AuthController` + `SocialAuthController` | inventori `app/Http/Controllers/` |
| Request | Konvensi `Store{Entity}Request.php` / `Update{Entity}Request.php` di `app/Http/Requests/{Module}/`; **tidak ada** `Destroy*Request` | `app/Http/Requests/Suppliers/StoreSupplierRequest.php`; `app/AGENTS.md` |
| Vite | `vite.config.ts` **tidak** punya pengaturan `host`; `package.json` `"dev": "vite"` tanpa `--host` | `vite.config.ts`, `package.json:6` |
| Test | Pest per-module `->group('{slug}')`; Playwright `workers` dari `PLAYWRIGHT_WORKERS` (default 1), `fullyParallel: true`; satu instance dipaksa `scripts/e2e-with-lock.sh`. Tidak ada angka "515 test" di repo (±106 spec sesuai `AGENTS.md`; hitung ulang saat eksekusi) | `playwright.config.ts:3-6,21-22`; `scripts/e2e-with-lock.sh:15-32,39`; `package.json:13` |
| Alur kerja | Sail via `sudo -n docker compose`, port ter-bind `127.0.0.1` (host ber-IP publik), **host tanpa php/composer/npm lokal** | sesi setup lokal 2026-11-19 |

> Sumber status aktif adalah GitHub issue `gmedia/erp`, bukan `task.md` / `task.handoff-archive.md` (deprecated — `AGENTS.md:55,77`, `.github/copilot-instructions.md:149-150`).

---

## 3. Keputusan arsitektur dan justifikasi singkat

### Mengapa Android-first

- **Tanpa biaya/mesin Apple**: tidak butuh Apple Developer ($99/th), macOS, atau Xcode.
- **Push lebih sederhana**: FCM saja (tanpa APNs/AppDelegate).
- **Kamera tanpa bug iOS**: native camera activity + Photo Picker, tanpa isu persistensi izin WebKit.
- **Distribusi fleksibel untuk on-premise**: Play publik, managed Google Play private, atau APK+MDM.
- **Cocok dengan pengguna operasional**: gudang, driver, staf cabang, field — mayoritas Android (termasuk perangkat rugged).
- **Biaya menunda iOS rendah**: Capacitor membuat iOS aditif; tidak ada perubahan React/backend.

### Mengapa tetap Capacitor (bukan PWA/Expo/Flutter)

- **PWA iOS** punya blocker (`beforeinstallprompt` tidak ada, izin kamera tidak persisten WebKit #327237, capture intermittent #282327) — tapi untuk Android pun kamera native lebih andal.
- **Capacitor v8** memakai ulang SPA (~100% UI), `takePhoto()` = native camera, FCM native.
- **Expo SDK 56 / Flutter 3.50** butuh rewrite 63 halaman — tidak ekonomis.

### Versi (Android)

| Paket | Versi | Catatan |
|---|---|---|
| `@capacitor/core`, `@capacitor/cli` | `8.x` (pin exact saat install) | v9 masih alpha; verifikasi dist-tag saat instalasi, jangan andaikan `latest-8` ada |
| `@capacitor/android` | `8.x` | cukup Android untuk v3; `@capacitor/ios` ditunda |
| `@capacitor/camera` | `8.2.x` | API `takePhoto`/`recordVideo`/`chooseFromGallery`. `resultType`/`source` dihapus; `allowEditing` → `editable` |
| `@capacitor/push-notifications` | `8.1.x` (stable) | alpha `9.x` ada — **jangan** dipakai produksi |
| `@aparajita/capacitor-secure-storage` | `8.0.x` | Android: Keystore + AES-GCM; web fallback = localStorage tak terenkripsi (development only) |
| `dotenv` | pin exact saat install | dipakai `capacitor.config.ts` di Node/CLI |
| Push Laravel | FCM HTTP v1 minimal in-app **atau** OneSignal — **OD-1** | `npav/laravel-fcm-push` v2.1.0 ada, tapi web-oriented (service worker + CSRF exception) → validasi dulu |

> **Perlu dikonfirmasi saat instalasi (jangan dihardcode):** dist-tag & versi persis `@capacitor/*` dan `@aparajita/capacitor-secure-storage`; apakah API `takePhoto` diperkenalkan di `8.0.0` atau `8.1.0`; serta tanggal/kebijakan terbaru **Google verified developer** (D-U-N-S + SHA-256). Cek `npm view <pkg> version` sebelum pin.

---

## 4. Prasyarat

### 4A — Prasyarat Android (per fase)

**Sebelum Fase 0 (spike shell):**
- [ ] **Android SDK/JDK** + emulator (atau device fisik).
- [ ] Akses instalasi paket npm lewat `sail npm` (host tanpa npm).

**Sebelum Fase 4 (push FCM):**
- [ ] **Firebase project** + `google-services.json` (Android) + service account FCM HTTP v1.
- [ ] **IP egress**: FCM `5228/5229/5230` + `443`, NAT timeout ≥30 menit.

**Sebelum Fase 7 (rilis/distribusi):**
- [ ] **Google Play Console** (bila distribusi lewat Play).
- [ ] **Keputusan distribusi Android (OD-2A)**.
- [ ] **Signing key** produksi (di luar repo) — SHA-256-nya dipakai `assetlinks.json` rilis.
- [ ] **Verifikasi developer Google** (D-U-N-S + signing SHA-256) **hanya bila** memakai managed Google Play private app di perangkat unmanaged — aturan berlaku sejak **2026-09-30**; perangkat fully-managed/Work Profile dikecualikan.

### 4B — Backlog iOS (TIDAK memblokir v3)

Ditunda sampai §12 dikerjakan:

- [ ] Akun Apple Developer berbayar + App ID + Push capability + APNs Auth Key.
- [ ] Firebase iOS: `GoogleService-Info.plist`.
- [ ] macOS + Xcode (atau CI macOS).
- [ ] Apple Business Manager / App Store Connect / TestFlight.
- [ ] `.well-known/apple-app-site-association` + Associated Domains.

> Apple mengunci **metode distribusi** setelah approval; Google mengunci **package name private permanen** bila memakai managed Google Play private. Putuskan OD-2A **sebelum** submit pertama.

---

## 5. Fase implementasi (Android)

### Fase 0 — Spike Android (1–2 hari, setelah prasyarat Fase 0)

**Tujuan:** validasi asumsi sebelum menyentuh kode produksi.

- [ ] Buat issue di `gmedia/erp` + klaim (`status:claimed`) → branch `feat/issue-N-capacitor-spike` dari `main`.
- [ ] `sail npm install --save-exact @capacitor/core@8 @capacitor/cli@8 @capacitor/android@8`; `sail npx cap init` dengan `appId` netral (`com.contoh.erp`), `webDir: dist`.
- [ ] `sail npm run build:mobile` (entry statis `index.html` + `vite.mobile.config.ts` → `dist`), `sail npx cap add android`, `sail npx cap sync android`.
- [ ] Verifikasi SPA tampil di emulator Android **tanpa perubahan UI**; catat versi JDK/Gradle/Android SDK.
- [ ] Catat: plugin berikut di-install **eksplisit di fasenya masing-masing**, bukan di Fase 0 — `dotenv` (Fase 2), `@capacitor/camera` (Fase 3), `@capacitor/push-notifications` (Fase 4), `@aparajita/capacitor-secure-storage` (Fase 5).
- [ ] **Gate:** bila SPA tidak tampil / runtime error WebView → STOP, evaluasi ulang.

**Exit criteria:** PR merger dengan bukti screenshot emulator + versi toolchain.

---

### Fase 1 — Fondasi backend push (3–4 hari, platform-agnostik)

**Tujuan:** backend bisa memilih dan mengirim channel `push` ke device token tanpa mengubah logika bisnis. Desain ini **siap iOS** (enum platform sudah mencakup keduanya).

File yang disentuh:

- `database/migrations/<timestamp>_create_device_tokens_table.php` (baru)
- `app/Models/DeviceToken.php` (baru) + `database/factories/DeviceTokenFactory.php`
- `app/Models/User.php` — **tambah relasi `deviceTokens(): HasMany`**
- `app/Services/NotificationService.php` — cabang channel `push`
- `app/Notifiables/ApprovalEvent.php`, `app/Notifiables/VehicleLoanDueEvent.php`, dan event lain yang ingin push — **tambah `'push'` ke `channels()`** (WAJIB)
- `config/notifications.php` — **tambah `'push'` ke array `channels`** untuk event yang diinginkan (lihat detail 3)
- `app/Services/Push/PushSender.php` (interface) + `FcmPushSender.php` (implementasi Android). Interface agnostik agar iOS tinggal menambah `ApnsPushSender`.
- `app/Actions/DeviceTokens/RegisterDeviceTokenAction.php`, `RevokeDeviceTokenAction.php` (baru — sesuai pola Action)
- `app/Http/Controllers/DeviceTokenController.php` (baru, **flat**)
- `app/Http/Requests/DeviceTokens/StoreDeviceTokenRequest.php` (baru). Untuk DELETE, gunakan route-model binding + Policy/`authorize()` di controller — repo **tidak** punya konvensi `Destroy*Request`.
- `app/Policies/DeviceTokenPolicy.php` (baru, untuk ownership `delete`)
- `app/Http/Controllers/Api/AuthController.php` — **wajib** disentuh untuk revocation saat logout (tambahkan ke daftar file fase ini)
- `routes/api/device-tokens.php` (baru) + **satu baris `require` manual** di dalam group `auth:sanctum` di `routes/api.php`
- `tests/Feature/DeviceTokens/...` (`->group('device-tokens')`), `tests/Feature/Notifications/PushChannelTest.php` (`->group('notifications')`)

Detail:

1. Tabel `device_tokens`: `id`, `user_id` (FK), `token` (unique), `platform` enum(`android`,`ios`), `device_name`, `last_seen_at`, timestamps. Satu user banyak device.
2. Rute:
   - `POST /api/device-tokens` — register/upsert; milik user terautentikasi.
   - `DELETE /api/device-tokens/{deviceToken}` — revoke **satu device**; authorize pemilik via `DeviceTokenPolicy`/`authorize()`.
   - `GET /api/device-tokens` — opsional, hanya milik sendiri.
   - Bungkus `Route::middleware('permission:{resource},true')` sesuai `routes/AGENTS.md` **atau** dokumentasikan pengecualian (OD-7).
3. **Seleksi channel (kunci):** `NotificationService::send()` memakai `array_intersect($event->channels(), $configured)`. Agar `push` benar-benar terkirim, **kedua** sisi harus memuat `push`:
   - `channels()` pada event ditambah `'push'`.
   - `$configured` (dari `config('notifications.events.{event}.channels')`) **juga** harus memuat `'push'`; **atau** ubah `send()` agar menggabungkan `push_enabled` ke `$configured` sebelum `array_intersect`. Pilih satu pendekatan dan tulis di PR. Menambah `'push'` hanya di `channels()` **tetap akan terfilter**.
   - `eventKey()`, `dedupeIdentity()`, payload shape **tidak diubah** (dedupe tetap berlaku).
4. Cabang pengiriman:
   ```php
   if ($channel === 'push' && $recipient->deviceTokens()->exists()) {
       // PushSender::send($recipient->deviceTokens, $payload); dedupe_key tetap
   }
   ```
   Payload hanya `event_key` + `id` + `target_url` — **jangan** data ERP sensitif.
5. Simpan hasil provider (response/failure) ke log, bukan ke `data` database notification.
6. Token mati (`UnregisteredError`/`NotFound`) → hapus dari `device_tokens`.
7. **Logout**: `POST /api/logout` mencabut **semua** token device milik user; `DELETE /api/device-tokens/{deviceToken}` hanya mencabut device yang dituju. Keduanya didokumentasikan di PR dan diuji.

**Exit criteria:** `sail test --group device-tokens` dan `--group notifications` hijau; test membuktikan push **benar-benar masuk `channels()`**, `$configured`, dan terkirim ke fake sender; tidak ada regresi group existing.

---

### Fase 2 — Capacitor shell Android & App Links (2–3 hari)

- [ ] `sail npm install --save-exact dotenv` (pin exact saat install).
- [ ] `capacitor.config.ts`: `appId` netral; `appName` dari **`process.env.APP_NAME ?? process.env.VITE_APP_NAME ?? 'ERP'`** via `dotenv` (`dotenv` Node tidak meng-expand `${VAR}`, jadi prioritaskan `APP_NAME`; bukan `config()` PHP; bukan `import.meta.env` yang Vite-only), `webDir: 'dist'`, `loggingBehavior` debug hanya di dev. Default placeholder netral (Rule 0).
- [ ] Native API base URL: `resources/js/lib/axios.ts` memakai `import.meta.env.VITE_API_BASE_URL ?? ''`; tambahkan `VITE_API_BASE_URL=` di `.env.example` dan isi URL ERP absolut saat build mobile (kosong = relatif untuk web).
- [ ] CORS: `config/cors.php` mengizinkan origin native `http://localhost` (Android) dan `capacitor://localhost` (iOS backlog) selain `APP_URL`.
- [ ] `npm run build` + `cap sync android` jadi script (`package.json`: `mobile:sync`, `mobile:android`) — dijalankan lewat `sail npm` / CI.
- [ ] Deteksi runtime: `Capacitor.isNativePlatform()` di **satu** modul helper.
- [ ] Vite dev server di container: `--host 0.0.0.0` **hanya bila** diperlukan WebView; port host tetap `127.0.0.1` — **jangan** ubah binding docker-compose.
- [ ] App Links (Android): `App.addListener('appUrlOpen', …)` → parse `target_url` ke router React.
  - Verifikasi awal (Fase 2): pakai SHA-256 sertifikat **debug** di `public/.well-known/assetlinks.json`. Entri debug ini **wajib dihapus/diganti** sebelum rilis — jangan sampai ikut ter-ship (lihat Fase 7).
  - `assetlinks.json` **rilis** (SHA-256 signing key produksi) dibuat di **Fase 7** setelah signing key tersedia.
  - **On-premise multi-domain**: setiap domain deployment menyajikan `assetlinks.json` sendiri di `https://{domain}/.well-known/assetlinks.json` (package + SHA-256 sama). Dokumentasikan di panduan rilis.
- [ ] Splash/icon Android netral (placeholder generik, Rule 0).
- [ ] **Jangan** buat konfigurasi iOS di fase ini.

**Exit criteria:** App Link (debug) membuka halaman benar di emulator; desktop tidak berubah.

---

### Fase 3 — Kamera native Android dengan fallback web (2–3 hari)

- [ ] `sail npm install --save-exact @capacitor/camera@8.2.6` lalu `sail npx cap sync android` (konfirmasi versi via `npm view`).
- [ ] Buat abstraksi `resources/js/hooks/useCamera.ts` (atau `utils/camera.ts`):
  - native: `Camera.takePhoto({ quality: 80, editable: 'no' })` — **tanpa** `resultType`/`source`/`allowEditing`;
  - web: `File` dari `<input type="file" accept="image/*">` yang sudah ada;
  - **Konversi eksplisit**: `MediaResult.webPath ?? uri` → `fetch()` → `blob` → `new File([blob], name, { type })` agar kedua jalur menghasilkan `File` identik untuk Zod + `EntityForm`;
  - Struktur cabang disiapkan agar iOS tinggal menambah satu branch (tanpa ubah pemanggil).
- [ ] Ganti input di `pages/vehicle-loans/pickup/index.tsx` dan `.../return/index.tsx`; **pertahankan `photoMode` dan label**.
- [ ] `App.addListener('appRestoredResult', …)` (OS bisa mematikan app saat camera Activity jalan).
- [ ] Android: Photo Picker; izin hanya bila `saveToGallery: true`. (Key iOS ditunda — §12.)
- [ ] Kompresi ke target < 2 MB di helper yang sama.
- [ ] Ikuti UI golden rule — tidak membuat pola UI baru.

**Exit criteria:** selfie berhasil diambil + di-upload di Android; di browser perilaku identik dengan sebelum perubahan.

---

### Fase 4 — Push notification FCM Android (3–4 hari)

- [ ] `sail npm install --save-exact @capacitor/push-notifications@8.1.3` lalu `sail npx cap sync android` (konfirmasi versi via `npm view`).
- [ ] `PushNotifications.checkPermissions()` → `requestPermissions()` **di dalam user gesture** (tombol "Aktifkan notifikasi" di Settings, komponen shared yang ada), bukan otomatis saat launch.
- [ ] `register()` → `POST /api/device-tokens` (platform `android` + device name).
- [ ] Listener:
  - `pushNotificationReceived` (foreground) → in-app toast + simpan ke notification center existing;
  - `pushNotificationActionPerformed` → deep-link `target_url`.
- [ ] Android 13+: runtime `POST_NOTIFICATIONS`. Android 15 Private Space: dokumentasikan keterbatasan (app tidak bisa mendeteksi install di Private Space).
- [ ] `google-services.json` **tidak** di-commit (`.gitignore`).
- [ ] Uji foreground/background/killed **dengan notification payload**. Catat: Android data-only **tidak** memicu `pushNotificationReceived` saat killed → payload wajib (sesuai DoD).
- [ ] Tidak ada silent push — OD-3. Tidak ada APNs di fase ini.

**Exit criteria:** notifikasi approval request dengan payload sampai dalam ketiga keadaan; `target_url` benar.

---

### Fase 5 — Token storage (2–3 hari)

- [ ] `sail npm install --save-exact @aparajita/capacitor-secure-storage@8.0.1` lalu `sail npx cap sync android` (konfirmasi versi via `npm view`).
- [ ] Buat satu modul `resources/js/lib/token-storage.ts` dengan `getToken()/setToken()/clearToken()`:
  - native → `@aparajita/capacitor-secure-storage` (Android Keystore + AES-GCM);
  - web → `localStorage` (fallback yang sudah ada).
- [ ] Update **semua 8 pemanggil di 4 file**:
  - `resources/js/contexts/auth-context.tsx:119,155,168,181`
  - `resources/js/lib/axios.ts:11,23` (interceptor `Authorization: Bearer …`)
  - `resources/js/components/delete-user.tsx:35`
  - `resources/js/pages/user-guide/index.tsx:32`
- [ ] Tetap kirim Bearer lewat `axiosInstance`; jangan ubah kontrak API.
- [ ] **CSP ketat** dikonfigurasi dan diuji — secure storage hanya melindungi *at rest*, bukan XSS di WebView; tulis di PR handoff.
- [ ] Biometrik **opsional**, belum masuk DoD (OD-4): `@capawesome/capacitor-native-biometric` / Ionic Identity Vault.

**Exit criteria:** tidak ada `api_token` di `localStorage` saat native di kedelapan titik; login/logout/unduh user-guide tetap berfungsi; CSP tidak melanggar aturan existing.

---

### Fase 6 — PWA interim untuk iPhone & desktop (opsional tetapi disarankan, **serial**, 1–2 hari)

Fase ini adalah **jembatan** agar pengguna iPhone tetap bisa melakukan approval selama backlog iOS belum dikerjakan.

- [ ] `manifest.webmanifest`: `name`, `short_name`, `start_url`, `display: standalone`, ikon 192/512.
- [ ] Service worker sederhana: precache shell + network-first API; **jangan** cache endpoint sensitif.
- [ ] **Tidak ada Web Push / VAPID** di fase ini. iPhone tetap mendapat approval via browser; push iOS hanya lewat Home-Screen PWA dengan aturan "notifikasi wajib terlihat" (didokumentasikan, bukan DoD).
- [ ] Android: WebAPK terbentuk otomatis lewat Chrome bila kriteria install terpenuhi (bonus, bukan pengganti native).
- [ ] **Catatan konkurensi:** tidak dijalankan paralel — `AGENTS.md:22-45`.

**Exit criteria:** "Add to Home Screen" muncul di Chrome Android; approval berjalan di Safari iOS; tidak ada regresi desktop.

---

### Fase 7 — Distribusi Android & rilis (3–5 hari, setelah prasyarat Fase 7)

- [ ] Pilih jalur distribusi (OD-2A):
  - **Play publik** — review Google biasa.
  - **managed Google Play private app** — maks 1000 organisasi, internal track 100 tester; **package name private permanen**.
  - **APK + MDM (Intune/Workspace ONE)** — tanpa review store, cocok on-premise; update manual, peringatan Play Protect.
- [ ] Play Console record, signing key **di luar repo**, `google-services.json` tidak di-commit.
- [ ] Bila private app di perangkat unmanaged: selesaikan verifikasi developer (D-U-N-S + SHA-256).
- [ ] Generate `public/.well-known/assetlinks.json` **rilis** memakai SHA-256 signing key produksi, dan pastikan **entri SHA-256 debug sudah dihapus** (file rilis hanya berisi fingerprint produksi); dokumentasikan bahwa setiap domain on-premise menyajikan file ini di root domain-nya.
- [ ] CI build: `npm run build && npx cap sync android` sebelum Gradle/APK/AAB (via CI runner yang sesuai).
- [ ] Update flow: web assets boleh OTA; perubahan native (plugin, permission, config) wajib rilis baru.
- [ ] Handoff PR: versi plugin, hasil uji device, langkah rollback, status quality gate Sonar.

---

## 6. Konvensi wajib (AGENTS.md / copilot-instructions.md)

- Satu task = satu branch `feat/issue-N-slug` = satu PR dengan `Refs #N`.
- `rtk git` untuk git; **verifikasi** `git config --get core.hooksPath` → `.githooks`.
- Semua perintah runtime via `./vendor/bin/sail`. Host tanpa npm/php → perintah mobile juga lewat `sail npm` / CI.
- **Jangan tunggu CI/CD.**
- Maksimal 3 tool call per turn; serial setelah interrupt. Tidak ada parallel agent swarm.
- Rule 0: tanpa `'gmedia'`, domain, tenant, logo, atau nama korporat di kode/UI/migrasi/seeder/test/docs generik. Kecuali identifier infra repo (`gmedia/erp`, `gmedia_erp`).
- Test group kebab-case, satu group per test. `Sanctum::actingAs()`, `assertJson()` — bukan `actingAs()`/`assertInertia()`.
- `sail bin duster fix` setelah perubahan PHP; `npm run types` sebelum merge; SonarQube MCP untuk quality gate.
- UI: satu pola per kebutuhan; prop form `entity`; `ViewModal` = `memo()` + `ViewModalShell` + `ViewField`; jangan ubah `data-testid`.

---

## 7. Testing & QA (Android)

| Level | Perintah | Catatan |
|---|---|---|
| Backend | `sail test --group device-tokens`, `--group notifications`, `--group approvals` | Test expired/invalid token, revocation saat logout, **pemilihan channel push** |
| Frontend | `sail npm run types`, `sail npm run lint:check`, `sail npm run format:check` | Semua lewat Sail |
| E2E existing | `npm run test:e2e` (= prepare + `sh ./scripts/e2e-with-lock.sh npx playwright test …`) | Satu instance, `workers` default 1; jangan jalankan di chat |
| Device Android | Emulator + device fisik | Push background/killed tidak bisa diverifikasi di browser |
| Manual | Internal test track / APK UAT | Checklist: install, login, kamera, push, deep link, logout (token device terhapus) |
| Quality gate | SonarQube MCP (`projectKey=gmedia_erp`) | Wajib sebelum merge (`AGENTS.md`) |

---

## 8. Keamanan — ringkasan risiko (Android)

| Risiko | Mitigasi | Status |
|---|---|---|
| Token di `localStorage` → XSS | secure storage Android + CSP ketat; **tidak** menyelesaikan XSS di WebView | Fase 5 |
| `Preferences` bukan secure storage | jangan dipakai untuk token (SharedPreferences; fallback localStorage di web) | Desain |
| Secure storage web = localStorage tak terenkripsi | hanya development; produksi native saja | Desain |
| Deep link menyisipkan token | jangan taruh Bearer token di URL; kirim hanya `event_key`/id, detail via API setelah auth | Fase 1/2 |
| Token device tidak dicabut saat logout | revocation di Fase 1 (logout = semua device; DELETE = satu device) | Fase 1 |
| `google-services.json` / keystore di repo | `.gitignore` + CI secrets | Fase 4/7 |
| Payload push berisi data ERP sensitif | kirim hanya `event_key` + id + `target_url` | Fase 1 |
| Play Protect memblokir APK sideload | pertimbangkan managed Google Play; dokumentasikan di panduan rilis | Fase 7 |
| Package name terkunci private permanen | putuskan OD-2A sebelum submit pertama | Blocker Fase 7 |

---

## 9. Open decisions

- **OD-1 — Provider push Laravel (Android):** FCM HTTP v1 minimal in-app vs OneSignal. `npav/laravel-fcm-push` v2.1.0 ada tapi web-oriented (service worker + CSRF exception) → validasi dulu; jangan langsung dipilih.
- **OD-2A — Distribusi Android (sekarang):** Play publik vs managed Google Play private (package name terkunci) vs APK+MDM. **Putuskan sebelum Fase 7.**
- **OD-2B — Distribusi iOS (backlog):** App Store publik + TestFlight vs Apple Business Manager custom app. **Sticky** — lihat §12.
- **OD-3 — Push silent:** apakah ada notifikasi yang harus silent? Plugin tidak mendukung silent push iOS; di Android gunakan notification payload untuk kepentingan DoD.
- **OD-4 — Biometrik:** masuk Fase 5 atau delay? Saat ini **tidak** masuk DoD.
- **OD-5 — Issue & claim:** buat issue baru di `gmedia/erp` per fase; semua PR butuh `Refs #N` + label `status:claimed`.
- **OD-6 — Prasyarat Android:** Firebase project (Fase 4) & Play Console (Fase 7) sudah tersedia? **Blocker Fase 4/7, bukan Fase 0.**
- **OD-7 — Permission wrapper rute device-tokens:** semua user boleh mendaftarkan tokennya (dokumentasikan pengecualian `permission:`) atau butuh permission resource baru?
- **OD-8 — Kebutuhan iPhone interim:** apakah PWA (approval saja, tanpa kamera/push handal) cukup sampai backlog iOS dikerjakan?

---

## 10. Out of scope (v3)

- **Native iOS** (install/push APNs/kamera iOS/Keychain/distribusi Apple) — dipindah ke backlog §12, bukan dibuang.
- Rewrite UI ke React Native / Flutter.
- Migrasi auth Bearer → cookie Sanctum SPA (utang desain terpisah).
- Web Push / VAPID PWA di fase awal.
- Offline-first penuh dengan SQLite/encrypted local DB.
- Multi-currency FX, P&L per departemen (#114), optimasi CI (#162).

---

## 11. Checklist sebelum merge tiap fase

- [ ] `git status` bersih; tidak ada file generated/credential yang ter-commit.
- [ ] `sail test --group {slug}` hijau.
- [ ] `sail npm run types`, `lint:check`, `format:check` hijau.
- [ ] `sail bin duster fix` dijalankan bila ada perubahan PHP.
- [ ] SonarQube quality gate dicek via MCP.
- [ ] PR description memuat `Refs #N`, handoff (what/files/verification/next steps), bukti device Android bila relevan.
- [ ] Tidak ada brand hardcoded (Rule 0).
- [ ] `git config --get core.hooksPath` mengembalikan `.githooks`.
- [ ] `<!-- checkpoint -->` ditulis di issue bila pekerjaan belum selesai.

---

## 12. Backlog iOS (ditunda — aditif, tanpa rewrite)

Dikerjakan setelah Android stabil. **Tidak perlu** mengubah React SPA, backend `device_tokens`, atau `token-storage.ts` — strukturnya sudah dirancang dual-platform.

- [ ] `sail npm install @capacitor/ios@8`; `sail npx cap add ios`; buat project Xcode (macOS/CI macOS).
- [ ] Akun Apple Developer + App ID + Push Notifications capability + APNs Auth Key.
- [ ] Firebase iOS: `GoogleService-Info.plist` (tidak di-commit); integrasi Firebase Messaging via SPM/CocoaPods.
- [ ] AppDelegate forward APNs token; listener `pushNotificationActionPerformed` (sudah generik).
- [ ] Tambah `ApnsPushSender` di balik interface `PushSender` yang sudah dibuat di Fase 1.
- [ ] `NSCameraUsageDescription`, `NSPhotoLibraryAddUsageDescription`, `NSPhotoLibraryUsageDescription`; tambah branch iOS di `useCamera`.
- [ ] Branch Keychain di `token-storage.ts` (`@aparajita/capacitor-secure-storage` sudah mendukung).
- [ ] `public/.well-known/apple-app-site-association` + Associated Domains (Universal Links) per domain deployment.
- [ ] Distribusi Apple (OD-2B): App Store publik + TestFlight atau Apple Business Manager custom app; **metode terkunci setelah approval**.
- [ ] Ganti PWA interim (§5 Fase 6) untuk kebutuhan kamera/push iPhone yang tidak bisa dipenuhi web.

**Estimasi tambahan iOS:** Fase 2/4/5/7 versi iOS ± setara Android, minus pekerjaan backend (sudah selesai). Yang paling mahal adalah setup akun/Xcode + review distribusi.

---

## 13. Perubahan versi

| Versi | Perubahan |
|---|---|
| v1 → v2 | Perbaikan review deep agent: `channels()` wajib `push`; `takePhoto({ editable: 'no' })`; path controller flat + `require` manual + `permission:`; 8 token call site; hapus angka "515"; prasyarat sebelum Fase 0; DoD scoped notification-payload; hapus PKCE; serial not parallel; versi secure storage. |
| v2 → v2.1 | Label 7→8 pemanggil; route logout diseragamkan (`DELETE /api/device-tokens/{deviceToken}`); `import.meta.env` → `process.env` + dotenv di `capacitor.config.ts`. |
| v2.1 → v3 | **Android-first**: Fase 0/2/4/5/7 dipersempit ke Android; §4 dipisah 4A/4B; tambah `PushSender` interface + `FcmPushSender`; PWA dijadikan jembatan iPhone; tambah §12 backlog iOS; OD-2 dipecah 2A/2B; tambah `assetlinks.json` + App Links; hapus APNs/Xcode/Apple dari alur aktif. |
| v3 → v3.1 | Menutup temuan verifikasi v3: (1) langkah install plugin eksplisit per fase (`dotenv`, camera, push, secure storage); (2) `assetlinks.json` rilis dipindah ke Fase 7 + catatan multi-domain + SHA-256 signing; (3) klaim "sudah" → "akan" di prinsip; (4) §4A dipecah per fase (Fase 0/4/7); (5) Fase 6 opsional diselaraskan dengan §1; (6) `DestroyDeviceTokenRequest` diganti Policy/authorize; (7) seleksi push dipertegas: `channels()` **dan** `$configured` harus memuat `push`; (8) sitasi `e2e-with-lock.sh:15-32,39` & jumlah spec; (9) tambah catatan "perlu dikonfirmasi saat instalasi"; (10) tambah `AuthController` ke daftar file Fase 1. |
| v3.1 → v3.2 | Menutup sisa minor verifikasi v3.1: typo "economic" → "ekonomis"; instruksi eksplisit menghapus/mengganti entri SHA-256 **debug** di `assetlinks.json` sebelum rilis (Fase 2 dan Fase 7). |
| v3.2 → v3.3 | Menyelaraskan rencana dengan fakta Fase 0: build mobile statis (`index.html` + `vite.mobile.config.ts`, `publicDir:false`, alias `@`), `VITE_API_BASE_URL` + axios baseURL, CORS origin native, `APP_NAME` precedence (dotenv tanpa expand), script `build:mobile`/`mobile:sync`, `.gitignore` `dist`, dan catatan blocker toolchain. |
| v3.3 → v3.4 | Fase 1 dieksekusi: device_tokens table/model/factory, kontrak `PushSender` + `LogPushSender` (+ binding), channel `push` di `NotificationService` (dedupe Cache), events/config `push`, API device-tokens + Policy + logout revocation, 11 test baru (6 device-tokens + 5 notifications), regresi approvals/vehicle-loans hijau, PHPStan/Duster clean. **Fix:** lookup config event bertitik (mis. `approval.request.created`) memakai akses array literal, bukan dot traversal. |
| v3.4 → v3.5 | Fase 3/5 dieksekusi: `token-storage.ts` (cache + secure storage, 8 call site), `lib/camera.ts` + `PhotoInput` untuk pickup/return, `@capacitor/camera`/`@aparajita` sync. FCM HTTP v1 sender + Google service account token provider + tests (PR #207). Emulator CI: Android debug APK blocking; emulator smoke dibuat manual/opt-in karena runner hosted tanpa KVM; host punya `/dev/kvm`. |

---

## 14. Catatan implementasi Fase 0 (fakta lapangan, 2026-11-19)

- SPA Laravel memakai Blade shell (`resources/views/app.blade.php`); tidak ada `index.html` dan build default ke `public/build`. Untuk Capacitor dibuat entry statis `index.html` + `vite.mobile.config.ts` → `dist/` (`publicDir: false` agar `public/build` tidak ikut).
- Alias `@` harus ditambahkan manual di config mobile; `paths` tsconfig tidak otomatis dipakai Vite.
- `axios` diberi `baseURL` dari `VITE_API_BASE_URL` (default kosong = relatif untuk web).
- `config/cors.php` menambahkan `http://localhost` dan `capacitor://localhost`.
- `capacitor.config.ts` memakai `APP_NAME` lebih dulu karena `dotenv` Node tidak meng-expand `${APP_NAME}`.
- `appId` `com.contoh.erp` adalah placeholder dan **sticky** setelah publish.
- Host ini tidak punya Java/Android SDK/adb/emulator/Gradle; verifikasi emulator harus di mesin build/CI.
- Perbaikan environment: `node_modules`, `storage`, `bootstrap/cache` perlu di-`chown 1000:1000` setelah instalasi via root.
- ESLint harus mengabaikan `dist` dan `android` (tambahkan ke `ignores`).

---

## 15. Catatan implementasi Fase 1 (fakta lapangan, 2026-11-19)

- Migrasi `2026_11_19_122536_create_device_tokens_table.php`: `user_id` FK cascade, `token` unik, `platform` enum(`android`,`ios`), `device_name`, `last_seen_at`, index `(user_id, platform)`.
- Model `DeviceToken` + factory + relasi `User::deviceTokens()` (anotasi generik `HasMany<DeviceToken, $this>` untuk PHPStan).
- Kontrak `App\Contracts\PushSender` + `App\Services\Push\LogPushSender` (log driver). Binding di `AppServiceProvider::register()`. Driver FCM/OneSignal menunggu **OD-1**.
- `NotificationService`: branch `push` mengirim payload `event_key`/`title`/`body`/`target_url` ke semua device token user; dedupe memakai Cache `push:{dedupeKey}` (1 hari) agar tidak membuat baris notification DB.
- Event `ApprovalEvent` + `VehicleLoanDueEvent` dan `config/notifications.php` sama-sama memuat `push`.
- **Bug ditemukan & diperbaiki:** `config("notifications.events.{$eventKey}.channels")` gagal untuk event key bertitik karena Laravel memperlakukan titik sebagai nesting; diganti akses array literal `$events[$eventKey]['channels']`. Efek: channel `mail` approval kini benar-benar aktif (sebelumnya fallback `database`).
- API: `DeviceTokenController` (index/store/destroy), `StoreDeviceTokenRequest`, `DeviceTokenPolicy`, `Register/RevokeDeviceTokenAction`, `routes/api/device-tokens.php`, `require` di group `auth:sanctum`.
- Logout `AuthController` mencabut semua device token user.
- Test: `DeviceTokenControllerTest` (6) + `PushChannelTest` (5). Regresi `approvals` (12) + `vehicle-loans` (25) hijau. PHPStan & Duster clean.
- Penyesuaian test vehicle-loans: assertion notifikasi in-app memakai `whereNull('data->channel')`, konsisten dengan filter `NotificationController` (baris reservasi mail bukan notifikasi in-app).

---

## 16. Catatan implementasi Fase 3/5 + FCM (fakta lapangan, 2026-11-19)

- **Fase 5 (token storage):** `resources/js/lib/token-storage.ts` memakai cache in-memory sinkron + `initTokenStorage()` async. Native memakai `@aparajita/capacitor-secure-storage`; web tetap `localStorage`. `AuthProvider` menunggu init sebelum membaca token; 8 call site dimigrasi; axios 401 memakai `clearToken()`.
- **Fase 3 (kamera):** `resources/js/lib/camera.ts` memanggil `Camera.takePhoto({ quality: 80, editable: 'no' })` lalu mengonversi `webPath`/`uri` menjadi `File`. `PhotoInput` menampilkan tombol kamera di native dan input file lama di web. Pickup/return sudah memakainya. `appRestoredResult` belum diimplementasikan (follow-up).
- **FCM:** `FcmPushSender` memanggil FCM v1 `messages:send`; `GoogleServiceAccountTokenProvider` memakai `google/auth` dan cache 50 menit. `PUSH_DRIVER=log` default, `fcm` opt-in. Token unregistered dihapus; kegagalan HTTP dicatat. 3 test baru (sukses, unregistered, server error).
- **CI mobile:** `mobile_android` membangun debug APK dan bersifat blocking. `mobile_emulator` dibuat **manual/opt-in** (`workflow_dispatch`) karena runner GitHub hosted tidak punya KVM; host ini punya `/dev/kvm` tetapi belum punya JDK/SDK/libs.
- **Status runtime:** login/logout native dan capture kamera belum diverifikasi di device/emulator. Verifikasi hanya sampai type-check, web build, dan kompilasi APK.
- **appId:** masih placeholder `com.contoh.erp`; wajib diganti sebelum build internal pertama.
