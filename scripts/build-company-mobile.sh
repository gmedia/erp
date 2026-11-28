#!/usr/bin/env bash
set -euo pipefail

DRY_RUN=0
VERSION_NAME="${MOBILE_VERSION_NAME:-}"
VERSION_CODE="${MOBILE_VERSION_CODE:-}"
POSITIONAL=()

usage() {
    cat >&2 <<'EOF'
Usage: MOBILE_APP_ID_PREFIX=<prefix> scripts/build-company-mobile.sh [options] \
         <company-slug> <company-name> <api-url> <google-services.json> \
         [keystore-file] [keystore-password] [key-alias] [key-password]

Example:
  MOBILE_APP_ID_PREFIX=com.perusahaan.contoh scripts/build-company-mobile.sh \
    nama-perusahaan "Nama Perusahaan" https://<slug>.app.perusahaan.contoh ./google-services.json

Options:
  --version-name=<name>  Versi aplikasi (default env MOBILE_VERSION_NAME). Wajib pada mode release.
  --version-code=<n>     versionCode integer (default env MOBILE_VERSION_CODE). Wajib pada mode release.
  --dry-run              Hanya validasi argumen dan tampilkan rencana output (tanpa build).

MOBILE_APP_ID_PREFIX wajib diisi: appId = ${MOBILE_APP_ID_PREFIX}.app_<slug>.
Pakai awalan yang sama dengan build sebelumnya (lihat docs/mobile-multi-company-build.md).
EOF
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        --version-name=*)
            VERSION_NAME="${1#*=}"
            shift
            ;;
        --version-name)
            if [ "$#" -lt 2 ]; then
                echo "Opsi --version-name butuh nilai." >&2
                exit 1
            fi
            VERSION_NAME="$2"
            shift 2
            ;;
        --version-code=*)
            VERSION_CODE="${1#*=}"
            shift
            ;;
        --version-code)
            if [ "$#" -lt 2 ]; then
                echo "Opsi --version-code butuh nilai." >&2
                exit 1
            fi
            VERSION_CODE="$2"
            shift 2
            ;;
        --help | -h)
            usage
            exit 0
            ;;
        --)
            shift
            POSITIONAL+=("$@")
            break
            ;;
        -*)
            echo "Opsi tidak dikenal: $1" >&2
            usage
            exit 1
            ;;
        *)
            POSITIONAL+=("$1")
            shift
            ;;
    esac
done

if [ -z "${MOBILE_APP_ID_PREFIX:-}" ]; then
    echo "MOBILE_APP_ID_PREFIX belum diisi. Set awalan appId (mis. com.perusahaan.contoh)" >&2
    echo "yang sama dengan build sebelumnya." >&2
    usage
    exit 1
fi

if ! printf '%s' "$MOBILE_APP_ID_PREFIX" | grep -Eq '^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$'; then
    echo "MOBILE_APP_ID_PREFIX tidak valid: '$MOBILE_APP_ID_PREFIX'." >&2
    echo "Gunakan huruf kecil, angka, underscore, dipisah titik (mis. com.perusahaan.contoh), tanpa spasi." >&2
    exit 1
fi

if [ "${#POSITIONAL[@]}" -lt 4 ]; then
    usage
    exit 1
fi

COMPANY_SLUG="${POSITIONAL[0]}"
COMPANY_NAME="${POSITIONAL[1]}"
API_URL="${POSITIONAL[2]}"
GOOGLE_SERVICES_JSON="${POSITIONAL[3]}"

KEYSTORE_FILE="${POSITIONAL[4]:-}"
KEYSTORE_PASSWORD="${POSITIONAL[5]:-}"
KEY_ALIAS="${POSITIONAL[6]:-}"
KEY_PASSWORD="${POSITIONAL[7]:-}"

if [ -z "$COMPANY_SLUG" ]; then
    echo "company-slug tidak boleh kosong." >&2
    exit 1
fi

if [ ! -f "$GOOGLE_SERVICES_JSON" ]; then
    echo "google-services.json not found: $GOOGLE_SERVICES_JSON" >&2
    exit 1
fi

GOOGLE_SERVICES_JSON="$(cd "$(dirname "$GOOGLE_SERVICES_JSON")" && pwd)/$(basename "$GOOGLE_SERVICES_JSON")"

IS_RELEASE=0
if [ -n "$KEYSTORE_FILE" ]; then
    IS_RELEASE=1
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
APP_ID="${MOBILE_APP_ID_PREFIX}.app_${app_slug}"

if [ -n "$VERSION_NAME" ] || [ -n "$VERSION_CODE" ]; then
    if [ -z "$VERSION_NAME" ] || [ -z "$VERSION_CODE" ]; then
        echo "MOBILE_VERSION_NAME dan MOBILE_VERSION_CODE harus diisi bersama." >&2
        exit 1
    fi

    if ! printf '%s' "$VERSION_NAME" | grep -Eq '^[0-9A-Za-z][0-9A-Za-z._-]*$'; then
        echo "MOBILE_VERSION_NAME tidak valid: '$VERSION_NAME'." >&2
        echo "Gunakan huruf, angka, titik, dash, underscore." >&2
        exit 1
    fi

    if ! printf '%s' "$VERSION_CODE" | grep -Eq '^[1-9][0-9]*$'; then
        echo "MOBILE_VERSION_CODE tidak valid: '$VERSION_CODE'. Harus integer >= 1." >&2
        exit 1
    fi
elif [ "$IS_RELEASE" -eq 1 ]; then
    echo "Mode release butuh versi: isi MOBILE_VERSION_NAME dan MOBILE_VERSION_CODE" >&2
    echo "(atau opsi --version-name/--version-code)." >&2
    exit 1
fi

package_name="$(python3 - "$GOOGLE_SERVICES_JSON" <<'PY'
import json
import sys

with open(sys.argv[1], encoding="utf-8") as handle:
    data = json.load(handle)
print(data["client"][0]["client_info"]["android_client_info"]["package_name"])
PY
)"

if [ "$package_name" != "$APP_ID" ]; then
    echo "Package mismatch: google-services.json has '$package_name', expected '$APP_ID'." >&2
    exit 1
fi

REPO_ROOT="$(git rev-parse --show-toplevel)"
OUT_DIR="$REPO_ROOT/dist-mobile/$COMPANY_SLUG"

if [ "$IS_RELEASE" -eq 1 ] && [ -f "$OUT_DIR/release.json" ]; then
    PREV_VERSION_CODE="$(python3 - "$OUT_DIR/release.json" <<'PY'
import json
import sys

try:
    with open(sys.argv[1], encoding="utf-8") as handle:
        print(int(json.load(handle)["version_code"]))
except (OSError, KeyError, TypeError, ValueError):
    print("")
PY
)"
    if printf '%s' "$PREV_VERSION_CODE" | grep -Eq '^[0-9]+$' && [ "$VERSION_CODE" -le "$PREV_VERSION_CODE" ]; then
        echo "MOBILE_VERSION_CODE=$VERSION_CODE harus lebih besar dari rilis sebelumnya ($PREV_VERSION_CODE)." >&2
        exit 1
    fi
fi

if [ "$DRY_RUN" -eq 1 ]; then
    echo "DRY RUN OK"
    echo "appId: $APP_ID"
    if [ -n "$VERSION_NAME" ]; then
        echo "versionName: $VERSION_NAME"
        echo "versionCode: $VERSION_CODE"
    fi
    if [ "$IS_RELEASE" -eq 1 ]; then
        echo "outputs: $OUT_DIR/app-release.apk"
        echo "         $OUT_DIR/app-release.apk.sha256"
        echo "         $OUT_DIR/app-release.aab"
        echo "         $OUT_DIR/release.json"
    else
        echo "outputs: $OUT_DIR/app-debug.apk"
    fi
    exit 0
fi

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

if [ -n "$VERSION_NAME" ]; then
    export VITE_APP_VERSION="$VERSION_NAME"
    export VITE_APP_VERSION_CODE="$VERSION_CODE"
fi

rm -rf android
npx cap add android
cp "$GOOGLE_SERVICES_JSON" android/app/google-services.json

if [ -n "$VERSION_NAME" ]; then
    sed -i -E "s/^([[:space:]]*)versionCode [0-9]+/\1versionCode ${VERSION_CODE}/" android/app/build.gradle
    sed -i -E "s/^([[:space:]]*)versionName \"[^\"]*\"/\1versionName \"${VERSION_NAME}\"/" android/app/build.gradle
    if ! grep -q "versionCode ${VERSION_CODE}" android/app/build.gradle; then
        echo "Gagal menulis versionCode ke android/app/build.gradle." >&2
        exit 1
    fi
fi

npx capacitor-assets generate --android \
    --iconBackgroundColor '#0f172a' \
    --iconBackgroundColorDark '#0f172a' \
    --splashBackgroundColor '#0f172a' \
    --splashBackgroundColorDark '#0f172a'

npm run mobile:sync

mkdir -p "$OUT_DIR"

sha256_file() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | cut -d' ' -f1
    else
        shasum -a 256 "$1" | cut -d' ' -f1
    fi
}

if [ "$IS_RELEASE" -eq 1 ]; then
    (cd android && ./gradlew bundleRelease assembleRelease \
        -Pandroid.injected.signing.store.file="$KEYSTORE_FILE" \
        -Pandroid.injected.signing.store.password="$KEYSTORE_PASSWORD" \
        -Pandroid.injected.signing.key.alias="$KEY_ALIAS" \
        -Pandroid.injected.signing.key.password="$KEY_PASSWORD")

    cp android/app/build/outputs/bundle/release/app-release.aab "$OUT_DIR/app-release.aab"

    APK_PATH="android/app/build/outputs/apk/release/app-release.apk"
    if [ ! -f "$APK_PATH" ]; then
        APK_PATH="android/app/build/outputs/apk/release/app-release-unsigned.apk"
    fi
    if [ ! -f "$APK_PATH" ]; then
        echo "APK release tidak ditemukan di android/app/build/outputs/apk/release/." >&2
        exit 1
    fi
    case "$APK_PATH" in
        *unsigned*)
            echo "APK release tidak bertanda tangan; periksa keystore dan properti signing." >&2
            exit 1
            ;;
    esac

    cp "$APK_PATH" "$OUT_DIR/app-release.apk"

    SHA256="$(sha256_file "$OUT_DIR/app-release.apk")"
    SIZE_BYTES="$(wc -c < "$OUT_DIR/app-release.apk" | tr -d ' ')"
    printf '%s  %s\n' "$SHA256" "app-release.apk" > "$OUT_DIR/app-release.apk.sha256"

    python3 - "$OUT_DIR/release.json" "$VERSION_NAME" "$VERSION_CODE" "$SHA256" "$SIZE_BYTES" "$APP_ID" <<'PY'
import json
import sys

path, version_name, version_code, sha256, size_bytes, app_id = sys.argv[1:7]
with open(path, "w", encoding="utf-8") as handle:
    json.dump(
        {
            "version_name": version_name,
            "version_code": int(version_code),
            "sha256": sha256,
            "size_bytes": int(size_bytes),
            "app_id": app_id,
        },
        handle,
        indent=2,
    )
    handle.write("\n")
PY

    echo "AAB: $OUT_DIR/app-release.aab"
    echo "APK: $OUT_DIR/app-release.apk"
    echo "SHA256: $OUT_DIR/app-release.apk.sha256"
    echo "Metadata: $OUT_DIR/release.json"
else
    (cd android && ./gradlew assembleDebug)
    cp android/app/build/outputs/apk/debug/app-debug.apk "$OUT_DIR/app-debug.apk"
    echo "APK: $OUT_DIR/app-debug.apk"
fi
