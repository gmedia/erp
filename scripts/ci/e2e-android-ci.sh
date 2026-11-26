#!/usr/bin/env bash
# CI wrapper untuk langkah android-emulator-runner di .github/workflows/e2e-android.yml.
# Runner menjalankan input `script` baris per baris (sh -c), jadi logika multi-baris
# dipindah ke sini. Emulator sudah dijalankan runner; skrip ini tidak boot/matikan emulator.
# Bila E2E gagal: kumpulkan logcat + screenshot ke android-artifacts/ lalu exit dengan status asli.
set -euo pipefail

export E2E_ANDROID_SKIP_EMULATOR=1

status=0
bash scripts/e2e-android.sh || status=$?

if [ "$status" -ne 0 ]; then
    mkdir -p android-artifacts
    adb logcat -d > android-artifacts/logcat.txt 2>/dev/null || true
    adb exec-out screencap -p > android-artifacts/screen.png 2>/dev/null || true
    echo "E2E Android gagal (exit ${status}); artefak ada di android-artifacts/" >&2
    exit "$status"
fi
