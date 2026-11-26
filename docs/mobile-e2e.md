# Mobile E2E (Android emulator)

Dokumen ini mencatat prasyarat dan langkah instalasi untuk smoke test dan E2E Android (Capacitor). Issue: #547.

## Identitas aplikasi

- `applicationId` / namespace: `com.example.erp` (sumber: `android/app/build.gradle`).
- `capacitor.config.ts` membaca `CAPACITOR_APP_ID`, default `com.example.erp`.
- Job `mobile_emulator` di `.github/workflows/tests.yml` memakai env `ANDROID_APP_ID: com.example.erp` di level job. Ubah nilai itu bila `applicationId` berubah.

## Prasyarat mesin

| Komponen | Versi | Lokasi |
|----------|-------|--------|
| JDK | 21 (Temurin) | `~/opt/jdk-21.0.13.1+2` |
| Android cmdline-tools | 11076708 | `~/Android/Sdk/cmdline-tools/latest` |
| platform-tools, emulator | terbaru dari sdkmanager | `~/Android/Sdk` |
| Platform | `platforms;android-34` | `~/Android/Sdk/platforms` |
| System image | `system-images;android-34;google_apis;x86_64` | `~/Android/Sdk/system-images` |
| Build tools | `build-tools;34.0.0` | `~/Android/Sdk/build-tools` |
| AVD | `erp_api34` (pixel_5) | `~/.android/avd` |

KVM wajib: `/dev/kvm` harus bisa diakses user yang menjalankan emulator (grup `kvm` atau ACL). Tanpa itu emulator x86_64 menolak boot dengan pesan "requires hardware acceleration". Cek cepat: `test -r /dev/kvm && test -w /dev/kvm && echo OK`. `emulator -accel-check` harus melaporkan akselerasi KVM, bukan `LINE_NOT_FOUND`.

Status verifikasi (per mesin, dicatat agar tidak diulang):
- JDK 21 Temurin terpasang di `~/opt/jdk-21.0.13.1+2` (`java -version` OK).
- cmdline-tools, platform-tools, emulator, `platforms;android-34`, `system-images;android-34;google_apis;x86_64`, `build-tools;34.0.0`, dan AVD `erp_api34` sudah ada di `~/Android/Sdk`.
- Akses `/dev/kvm` sudah tersedia untuk user agent (verifikasi: `test -r /dev/kvm && test -w /dev/kvm`).
- `bash scripts/e2e-android.sh` lulus di mesin ini: 2 passed (login + Departments), sekitar 140 dtk total dengan build, cap sync, gradle, boot emulator, dan install APK.

## Langkah instalasi (sekali per mesin)

1. JDK 21: unduh tarball Temurin 21 (linux x64) dari Adoptium, ekstrak ke `~/opt`. Tanpa sudo.
2. Android cmdline-tools: unduh zip `commandlinetools-linux-*_latest.zip` dari dl.google.com, ekstrak ke `~/Android/Sdk/cmdline-tools/latest` (isi zip ada di subfolder `cmdline-tools/`, rename menjadi `latest`).
3. Env:
   ```bash
   export JAVA_HOME=~/opt/jdk-21.0.13.1+2
   export ANDROID_HOME=~/Android/Sdk
   export ANDROID_SDK_ROOT=$ANDROID_HOME
   export PATH="$JAVA_HOME/bin:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools:$ANDROID_HOME/emulator:$PATH"
   ```
4. Lisensi dan paket:
   ```bash
   yes | sdkmanager --licenses
   sdkmanager "platform-tools" "emulator" "platforms;android-34" \
     "system-images;android-34;google_apis;x86_64" "build-tools;34.0.0"
   ```
5. AVD headless:
   ```bash
   echo no | avdmanager create avd -n erp_api34 \
     -k "system-images;android-34;google_apis;x86_64" -d pixel_5 --force
   ```
6. Boot test (matikan dengan `adb emu kill` setelah selesai):
   ```bash
   emulator -avd erp_api34 -no-window -gpu swiftshader_indirect -no-audio \
     -no-boot-anim -no-snapshot -memory 2048 -cores 2 &
   adb wait-for-device
   adb shell getprop sys.boot_completed
   ```

Batas sumber daya mesin pengembangan: 4 CPU, sekitar 8 GB RAM. Hentikan Vite dev dan proses berat lain sebelum emulator jalan.

## Workflow CI `e2e-android`

- File: `.github/workflows/e2e-android.yml` (terpisah dari `tests.yml`, tidak masuk job `ci`, tidak memblokir merge). Issue: #550.
- Pemicu: PR yang menyentuh path mobile (lihat `paths` di workflow), jadwal harian, dan `workflow_dispatch`.
- Alur: aktifkan KVM via udev, Sail + `migrate:fresh --seed`, lalu `android-emulator-runner` (API 34, `-no-window`) yang memanggil `scripts/e2e-android.sh` dengan `E2E_ANDROID_SKIP_EMULATOR=1`. Backend untuk emulator = `http://10.0.2.2:82`.
- Artefak saat gagal: laporan Playwright (`e2e/test-results-android`), logcat, screenshot, dan log Laravel.
- `E2E_ANDROID_SKIP_EMULATOR=1` memakai emulator yang sudah berjalan (tidak boot dan tidak dimatikan oleh script). Default lokal tidak berubah.

## Batasan

- Dialog native Android (izin, share sheet) tidak teruji oleh Playwright WebView.
- Emulator membutuhkan KVM. Tanpa KVM, tidak ada E2E Android lokal.
- Job `mobile_emulator` di CI hanya berjalan lewat `workflow_dispatch` dan `continue-on-error`.
