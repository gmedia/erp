#!/usr/bin/env sh
set -eu

LOCK_DIR=".e2e-run.lock"
PID_FILE="$LOCK_DIR/pid"
LOCK_HELD=""

cleanup() {
    if [ "$LOCK_HELD" = "1" ] && [ -d "$LOCK_DIR" ]; then
        rm -f "$PID_FILE" 2>/dev/null || true
        rmdir "$LOCK_DIR" 2>/dev/null || true
    fi
}

acquire_lock() {
    if [ -d "$LOCK_DIR" ]; then
        if [ -f "$PID_FILE" ]; then
            existing_pid="$(cat "$PID_FILE" 2>/dev/null || true)"
            if [ -n "$existing_pid" ] && kill -0 "$existing_pid" 2>/dev/null; then
                echo "Another E2E run is still active (pid $existing_pid)." >&2
                exit 1
            fi
        fi

        rm -rf "$LOCK_DIR" 2>/dev/null || true
    fi

    mkdir "$LOCK_DIR"
    echo "$$" > "$PID_FILE"
    LOCK_HELD="1"
    trap cleanup EXIT INT TERM HUP
}

if [ "$#" -eq 0 ]; then
    echo "Usage: sh ./scripts/e2e-with-lock.sh <command> [args...]" >&2
    exit 1
fi

acquire_lock
rm -f public/hot
"$@"
