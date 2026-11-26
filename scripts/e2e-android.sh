#!/usr/bin/env bash
# Local-only Android WebView E2E (PoC, Playwright _android). Not part of CI shards.
# Requires: KVM, Android SDK (ANDROID_HOME), JDK 21, AVD named by ANDROID_AVD (default erp_api34).
# Backend: Laravel Sail must be running (APP_PORT from docker ps, bound to 127.0.0.1).
set -euo pipefail

ANDROID_HOME="${ANDROID_HOME:-$HOME/Android/Sdk}"
ANDROID_AVD="${ANDROID_AVD:-erp_api34}"
export ANDROID_HOME
export PATH="$ANDROID_HOME/emulator:$ANDROID_HOME/platform-tools:$PATH"
if [ -x "${NODE22_HOME:-$HOME/opt/node22}/bin/node" ]; then
    export PATH="${NODE22_HOME:-$HOME/opt/node22}/bin:$PATH"
fi
if [ -d "$HOME/opt/jdk-21.0.13.1+2" ] && [ -z "${JAVA_HOME:-}" ]; then
    export JAVA_HOME="$HOME/opt/jdk-21.0.13.1+2"
    export PATH="$JAVA_HOME/bin:$PATH"
fi

APP_ID="${ANDROID_APP_ID:-com.example.erp}"
ADB_SERIAL="emulator-5554"
EMULATOR_PID=""

stop_emulator() {
    if [ -n "$EMULATOR_PID" ]; then
        adb -s "$ADB_SERIAL" emu kill >/dev/null 2>&1 || true
        wait "$EMULATOR_PID" 2>/dev/null || true
        EMULATOR_PID=""
    fi
}
trap stop_emulator EXIT

APP_PORT="${APP_PORT:-$(docker ps --format '{{.Names}} {{.Ports}}' \
    | grep -E 'laravel\.test|laravel-test' \
    | grep -oE '127\.0\.0\.1:[0-9]+->80/tcp' \
    | head -n1 | sed -E 's/127\.0\.0\.1:([0-9]+)->.*/\1/')}"
if [ -z "$APP_PORT" ]; then
    echo "APP_PORT tidak ditemukan dari docker ps (Sail belum jalan?)." >&2
    exit 1
fi

echo "[1/6] build bundle mobile (API via 10.0.2.2:${APP_PORT})"
E2E_ANDROID=1 VITE_API_BASE_URL="http://10.0.2.2:${APP_PORT}" npm run build:mobile

echo "[2/6] cap sync android"
E2E_ANDROID=1 npx cap sync android

echo "[3/6] gradle assembleDebug"
(cd android && ./gradlew assembleDebug --no-daemon -q)

echo "[4/6] boot emulator headless (${ANDROID_AVD})"
emulator -avd "$ANDROID_AVD" -no-window -no-snapshot -no-audio -no-boot-anim \
    -gpu swiftshader_indirect -memory 2048 -cores 2 -no-metrics >/dev/null 2>&1 &
EMULATOR_PID=$!
adb -s "$ADB_SERIAL" wait-for-device
until [ "$(adb -s "$ADB_SERIAL" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" = "1" ]; do
    sleep 5
done

echo "[5/6] install APK"
adb -s "$ADB_SERIAL" install -r android/app/build/outputs/apk/debug/app-debug.apk

echo "[6/6] playwright _android"
ANDROID_APP_ID="$APP_ID" npx playwright test -c playwright.android.config.ts
