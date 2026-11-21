#!/usr/bin/env bash
# e2e-full.sh — Full suite Playwright E2E di host / server dev (di luar CI).
#
# Pelajaran 2026-11-21 (issue #298): tanpa `config:cache`, web server
# (`artisan serve`) tidak membaca `DISABLE_RATE_LIMITING` dari env container
# → 429 massal (71 failed / 2.6h); dengan cache: 532 passed / 34 menit.
#
# Alur:
#   1. Hentikan Vite dev di container + hapus `public/hot`; `npm run build`.
#   2. `sail artisan config:cache`; `trap` → `config:clear` saat exit
#      (sukses / gagal / Ctrl-C), supaya Pest tetap memakai `.env.testing`.
#   3. Watchdog background: bila `bootstrap/cache/config.php` hilang selama
#      run → log + cache ulang (mencegah web server jatuh ke env container).
#   4. Daftar direktori sama dengan CI (find + .github/ci/e2e-excluded.txt).
#   5. PLAYWRIGHT_PHP_BINARY=scripts/sail-php, APP_ADMIN dari env
#      (default admin@example.com), --retries=1, 1 worker; argumen tambahan
#      diteruskan ke Playwright (mis. --shard=1/4, --reporter=line).
#   6. Ringkasan akhir (flaky/failed/skipped/passed + daftar test gagal);
#      exit code = exit code Playwright.
#
# Prasyarat:
#   - Sail container up (`./vendor/bin/sail up -d --wait`).
#   - Node 20 + npm di host, `node_modules/` sudah ada, browser Chromium
#     Playwright terpasang (npx playwright install chromium).
#   - Jangan jalankan dua instance bersamaan (config:cache global).
#
# Usage:
#   bash scripts/e2e-full.sh                       # seluruh direktori E2E (CI list)
#   bash scripts/e2e-full.sh tests/e2e/branches    # hanya modul tertentu
#   bash scripts/e2e-full.sh --shard=1/4 --reporter=line
#
# Env opsional:
#   E2E_FULL_PLAYWRIGHT_BASE_URL  default http://localhost:82 (port publish Sail)
#   APP_ADMIN                     default admin@example.com (harus = seed container)
#
# ATURAN: jangan menulis alamat/IP server atau kredensial di script ini;
#         jangan membaca atau mengubah .env.

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SAIL="./vendor/bin/sail"
CONFIG_CACHE_PATH="bootstrap/cache/config.php"
EXCLUDE_FILE=".github/ci/e2e-excluded.txt"
HOT_FILE="public/hot"
WATCHDOG_INTERVAL=5
LOG_DIR="e2e/e2e-full"
PW_LOG="$LOG_DIR/playwright.log"

# State (di-init sebelum trap agar aman di bawah set -u).
VITE_ACTIVE=0
WD_PID=""
PW_PID=""
PW_EXIT=""
CONFIG_CACHED=0

log() { printf '[e2e-full] %s\n' "$*"; }
log_err() { printf '[e2e-full] ERROR: %s\n' "$*" >&2; }

# Jalankan command Sail. Fallback: bila shell saat ini tidak punya akses
# socket Docker tetapi user terdaftar di group `docker` (mis. shell belum
# memuat ulang group), lompat lewat `sg docker` — tanpa menambah group baru.
sail_run() {
    if [ "${SAIL_VIA_SG:-0}" = "1" ]; then
        # Bangun command dengan quoting benar (printf %q) agar argumen
        # multi-kata / berisi kutip (mis. 'sh -c "..."') tidak rusak saat
        # dilewatkan ke 'sg docker -c'.
        local cmd="./vendor/bin/sail" a
        for a in "$@"; do
            cmd+=" $(printf '%q' "$a")"
        done
        sg docker -c "$cmd"
    else
        "$SAIL" "$@"
    fi
}

detect_docker_access() {
    if docker ps >/dev/null 2>&1; then
        return 0
    fi
    # Fallback: user terdaftar di group 'docker' di /etc/group tetapi shell ini
    # belum memuat group tersebut (mis. join baru / tmux lama). 'sg docker'
    # membaca ulang membership dari /etc/group, jadi tetap bisa dipakai.
    if command -v sg >/dev/null 2>&1 && sg docker -c 'docker ps' >/dev/null 2>&1; then
        SAIL_VIA_SG=1
        log "Akses Docker shell ini dibatasi; menggunakan 'sg docker' untuk Sail."
        return 0
    fi
    return 1
}

# config:cache — dipakai bootstrap awal dan watchdog. Kegagalan dicatat +
# return 1 (caller yang memutuskan).
cache_config() {
    if sail_run artisan config:cache; then
        CONFIG_CACHED=1
        log "config:cache OK (${CONFIG_CACHE_PATH})."
        return 0
    fi
    log_err "config:cache gagal."
    return 1
}

# Hentikan Vite dev di container; hapus public/hot.
# "[v]ite": bracket trick supaya pgrep tidak mematch command-nya sendiri.
stop_vite() {
    sail_run exec laravel.test sh -c 'pkill -f "[v]ite" 2>/dev/null' || true
    sleep 2
    rm -f "$HOT_FILE"
}

# Stop Vite dev bila aktif + pastikan public/hot hilang + build aset.
prepare_assets() {
    log "Memeriksa Vite dev di container..."
    if sail_run exec laravel.test sh -c 'pgrep -f "[v]ite" >/dev/null 2>&1'; then
        VITE_ACTIVE=1
        log "Vite dev aktif — menghentikan (start ulang bila perlu: ./vendor/bin/sail npm run dev:tunnel)."
        stop_vite
    else
        log "Vite dev tidak aktif."
    fi
    if [ -f "$HOT_FILE" ]; then
        log "Menghapus $HOT_FILE."
        rm -f "$HOT_FILE"
    fi

    log "Build aset Vite (npm run build)..."
    if ! npm run build; then
        log_err "npm run build gagal."
        return 1
    fi
    log "Build selesai."
    return 0
}

# Watchdog: tunggu config.php muncul, lalu pantau selama Playwright jalan;
# bila hilang → cache ulang. Berhenti saat Playwright selesai.
# PW_FILE berisi pid Playwright (dibaca per loop, bukan copy saat fork).
watchdog() {
    local i pid
    for i in $(seq 1 30); do
        [ -f "$CONFIG_CACHE_PATH" ] && break
        sleep 1
    done
    while :; do
        pid="$(cat "$PW_FILE" 2>/dev/null || true)"
        if [ -z "$pid" ] || ! kill -0 "$pid" 2>/dev/null; then
            break
        fi
        if [ ! -f "$CONFIG_CACHE_PATH" ]; then
            log "watchdog: ${CONFIG_CACHE_PATH} hilang — cache ulang."
            cache_config || true
        fi
        sleep "$WATCHDOG_INTERVAL"
    done
    return 0
}

# Dijalankan saat exit (normal/gagal/sinyal). Urutan:
#   1) hentikan watchdog,
#   2) hentikan Playwright bila masih jalan (bisa tertinggal setelah Ctrl-C),
#   3) config:clear (supaya Pest memakai .env.testing, bukan config dev),
#   4) hapus manual bila config:clear tidak berhasil.
# Tidak memanggil exit di sini; exit code (termasuk 130/143 dari sinyal)
# dipertahankan oleh trap sinyal terpisah.
cleanup() {
    if [ -n "${WD_PID}" ] && kill -0 "$WD_PID" 2>/dev/null; then
        kill "$WD_PID" 2>/dev/null || true
        wait "$WD_PID" 2>/dev/null || true
    fi
    if [ -n "${PW_PID}" ] && kill -0 "$PW_PID" 2>/dev/null; then
        log "cleanup: Playwright masih jalan — menghentikan."
        kill "$PW_PID" 2>/dev/null || true
        wait "$PW_PID" 2>/dev/null || true
    fi
    if [ "$CONFIG_CACHED" = "1" ]; then
        if sail_run artisan config:clear 2>/dev/null; then
            log "cleanup: config:clear OK."
        else
            log_err "cleanup: config:clear gagal; menghapus ${CONFIG_CACHE_PATH} manual."
            rm -f "$CONFIG_CACHE_PATH"
        fi
    fi
    if [ -f "$CONFIG_CACHE_PATH" ]; then
        log "cleanup: ${CONFIG_CACHE_PATH} masih ada setelah config:clear — hapus manual."
        rm -f "$CONFIG_CACHE_PATH"
    fi
    return 0
}

# --- 0. Guard & trap ---
# EXIT trap menjamin cleanup (config:clear) jalan di semua jalur. Trap sinyal
# set exit code standar (128+N) lalu exit; EXIT trap menyusul menjalankan
# cleanup, lalu proses keluar dengan kode sinyal tersebut.
if [ ! -x "$SAIL" ]; then
    log_err "$SAIL tidak ditemukan / tidak executable. Jalankan 'composer install'."
    exit 1
fi
if [ ! -f "$EXCLUDE_FILE" ]; then
    log_err "$EXCLUDE_FILE tidak ditemukan."
    exit 1
fi
if [ ! -d "tests/e2e" ]; then
    log_err "tests/e2e tidak ditemukan."
    exit 1
fi
if [ -f "$CONFIG_CACHE_PATH" ]; then
    log_err "${CONFIG_CACHE_PATH} sudah ada — kemungkinan run e2e-full lain masih"
    log_err "aktif, atau cache tidak bersih. Jalankan './vendor/bin/sail artisan"
    log_err "config:clear' lalu ulangi."
    exit 1
fi
if ! detect_docker_access; then
    log_err "Tidak bisa mengakses Docker (Socket permission denied dan user"
    log_err "bukan anggota group docker). Pastikan user terdaftar di group"
    log_err "'docker' (setelah join: login ulang / start ulang shell)."
    exit 1
fi
mkdir -p "$LOG_DIR"
trap 'cleanup' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP
# Abaikan SIGPIPE: bila stdout terputus (mis. '... | tee log' + Ctrl-C),
# cleanup tetap harus selesai menjalankan config:clear.
trap '' PIPE

# --- 1. Vite: stop + build ---
if ! prepare_assets; then
    exit 1
fi

# --- 2. config:cache ---
if ! cache_config; then
    log_err "Tidak bisa config:cache. Pastikan Sail container berjalan."
    exit 1
fi

# --- 3. Watchdog background ---
PW_FILE="$LOG_DIR/pw-pid"
: > "$PW_FILE"
watchdog &
WD_PID=$!
log "Watchdog aktif (pid $WD_PID)."

# --- 4. Tentukan target test ---
EXTRA_ARGS=("$@")

DIRS=()
if [ "${#EXTRA_ARGS[@]}" -eq 0 ]; then
    mapfile -t DIRS < <(
        find tests/e2e -mindepth 1 -maxdepth 1 -type d -printf '%f\n' \
            | sort \
            | grep -vxF -f "$EXCLUDE_FILE" \
            | sed 's#^#tests/e2e/#' || true
    )
    if [ "${#DIRS[@]}" -eq 0 ]; then
        log_err "Tidak ada direktori E2E terdeteksi (CI list)."
        exit 1
    fi
    log "E2E dirs (CI list, ${#DIRS[@]})."
else
    log "Target dari argumen: ${EXTRA_ARGS[*]}"
fi

# --- 5. Jalankan Playwright (host), 1 worker, --retries=1 ---
export PLAYWRIGHT_PHP_BINARY="scripts/sail-php"
export APP_ADMIN="${APP_ADMIN:-admin@example.com}"
export PLAYWRIGHT_BASE_URL="${E2E_FULL_PLAYWRIGHT_BASE_URL:-http://localhost:82}"
export PLAYWRIGHT_WORKERS="${PLAYWRIGHT_WORKERS:-1}"

log "Memulai Playwright (1 worker, --retries=1)..."
(
    set +e
    npx playwright test ${DIRS[@]+"${DIRS[@]}"} --retries=1 ${EXTRA_ARGS[@]+"${EXTRA_ARGS[@]}"}
) >"$PW_LOG" 2>&1 &
PW_PID=$!
echo "$PW_PID" > "$PW_FILE"

# --- 6. Wait + ambil exit code Playwright ---
wait "$PW_PID"
PW_EXIT=$?
PW_PID=""
wait "$WD_PID" 2>/dev/null || true
WD_PID=""

# --- 7. Ringkasan akhir ---
# Ringkasan Playwright berbentuk baris "N <status>" (indented); blok worker
# juga mencetak "N skipped (...)"/"N passed (...)", jadi ambil kemunculan
# terakhir tiap label sebagai final summary.
count_status() { # $1 = flaky|failed|skipped|passed
    awk -v s="$1" '$1 ~ /^[0-9]+$/ && $2 == s { print $1 }' "$PW_LOG" 2>/dev/null | tail -1
}
FLAKY="$(count_status flaky)"; FLAKY="${FLAKY:-0}"
FAILED="$(count_status failed)"; FAILED="${FAILED:-0}"
SKIPPED="$(count_status skipped)"; SKIPPED="${SKIPPED:-0}"
PASSED="$(count_status passed)"; PASSED="${PASSED:-0}"

FAILED_TESTS="$(grep -E '^[0-9]+\) ' "$PW_LOG" 2>/dev/null | sed -E 's/^[0-9]+\) //' || true)"

log "──────────────────────────────────────────────"
if [ "$PW_EXIT" = "0" ]; then
    log "E2E SUCCESS (exit Playwright: $PW_EXIT)"
else
    log "E2E FAILED (exit Playwright: $PW_EXIT)"
fi
log "  flaky=$FLAKY failed=$FAILED skipped=$SKIPPED passed=$PASSED"
if [ -n "$FAILED_TESTS" ]; then
    log "  Test gagal:"
    printf '%s\n' "$FAILED_TESTS" | sed 's/^/    - /'
fi
log "  Log lengkap: $PW_LOG"
log "──────────────────────────────────────────────"
if [ "$VITE_ACTIVE" = "1" ]; then
    log "Catatan: Vite dev sebelumnya aktif. Start ulang bila perlu:"
    log "  ./vendor/bin/sail npm run dev:tunnel"
fi

exit "$PW_EXIT"
