#!/usr/bin/env bash
set -euo pipefail

usage() {
    echo "Usage: $0 <company-slug> <company-name> <api-url> <google-services.json> [keystore-file] [keystore-password] [key-alias] [key-password]" >&2
    echo "Example: $0 nama-perusahaan \"Nama Perusahaan\" https://nama-perusahaan.app.dokfin.id ./google-services.json" >&2
}

if [ "$#" -lt 4 ]; then
    usage
    exit 1
fi

COMPANY_SLUG="$1"
COMPANY_NAME="$2"
API_URL="$3"
GOOGLE_SERVICES_JSON="$4"

KEYSTORE_FILE="${5:-}"
KEYSTORE_PASSWORD="${6:-}"
KEY_ALIAS="${7:-}"
KEY_PASSWORD="${8:-}"

if [ ! -f "$GOOGLE_SERVICES_JSON" ]; then
    echo "google-services.json not found: $GOOGLE_SERVICES_JSON" >&2
    exit 1
fi

GOOGLE_SERVICES_JSON="$(cd "$(dirname "$GOOGLE_SERVICES_JSON")" && pwd)/$(basename "$GOOGLE_SERVICES_JSON")"

if [ -n "$KEYSTORE_FILE" ]; then
    if [ ! -f "$KEYSTORE_FILE" ]; then
        echo "Keystore not found: $KEYSTORE_FILE" >&2
        exit 1
    fi

    KEYSTORE_FILE="$(cd "$(dirname "$KEYSTORE_FILE")" && pwd)/$(basename "$KEYSTORE_FILE")"
fi

case "$API_URL" in
    https://*) ;;
    *)
        echo "API URL must start with https://" >&2
        exit 1
        ;;
esac

app_slug="$(printf '%s' "$COMPANY_SLUG" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/_/g')"
APP_ID="com.dokfin.app_${app_slug}"

package_name="$(python3 -c 'import json,sys; data=json.load(open(sys.argv[1])); print(data["client"][0]["client_info"]["android_client_info"]["package_name"])' "$GOOGLE_SERVICES_JSON")"

if [ "$package_name" != "$APP_ID" ]; then
    echo "Package mismatch: google-services.json has '$package_name', expected '$APP_ID'." >&2
    exit 1
fi

REPO_ROOT="$(git rev-parse --show-toplevel)"
node_modules="$REPO_ROOT/node_modules"

if [ ! -d "$node_modules" ]; then
    echo "node_modules not found at $node_modules. Run npm install first." >&2
    exit 1
fi

WORKTREE="$(mktemp -u "/tmp/erp-mobile-${app_slug}-XXXXXX")"

cleanup() {
    git -C "$REPO_ROOT" worktree remove --force "$WORKTREE" >/dev/null 2>&1 || rm -rf "$WORKTREE"
}
trap cleanup EXIT

git -C "$REPO_ROOT" worktree add --detach "$WORKTREE" HEAD >/dev/null
ln -s "$node_modules" "$WORKTREE/node_modules"

cd "$WORKTREE"

export CAPACITOR_APP_ID="$APP_ID"
export CAPACITOR_APP_NAME="$COMPANY_NAME"
printf 'VITE_API_BASE_URL=%s\n' "$API_URL" > .env.mobile

rm -rf android
npx cap add android
cp "$GOOGLE_SERVICES_JSON" android/app/google-services.json

npx capacitor-assets generate --android \
    --iconBackgroundColor '#0f172a' \
    --iconBackgroundColorDark '#0f172a' \
    --splashBackgroundColor '#0f172a' \
    --splashBackgroundColorDark '#0f172a'

npm run mobile:sync

OUT_DIR="$REPO_ROOT/dist-mobile/$COMPANY_SLUG"
mkdir -p "$OUT_DIR"

if [ -n "$KEYSTORE_FILE" ]; then
    (cd android && ./gradlew bundleRelease \
        -Pandroid.injected.signing.store.file="$KEYSTORE_FILE" \
        -Pandroid.injected.signing.store.password="$KEYSTORE_PASSWORD" \
        -Pandroid.injected.signing.key.alias="$KEY_ALIAS" \
        -Pandroid.injected.signing.key.password="$KEY_PASSWORD")
    cp android/app/build/outputs/bundle/release/app-release.aab "$OUT_DIR/app-release.aab"
    echo "AAB: $OUT_DIR/app-release.aab"
else
    (cd android && ./gradlew assembleDebug)
    cp android/app/build/outputs/apk/debug/app-debug.apk "$OUT_DIR/app-debug.apk"
    echo "APK: $OUT_DIR/app-debug.apk"
fi
