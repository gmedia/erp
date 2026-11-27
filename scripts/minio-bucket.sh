#!/usr/bin/env bash
# minio-bucket.sh — Pastikan bucket MinIO dev (`local`) ada dan bisa diunduh publik.
#
# Idempoten: aman dijalankan berulang (setup awal maupun setup ulang / server yang
# sudah berjalan). Dipakai oleh project_setup.sh dan setup-env.sh.
#
# Kredensial dibaca dari container MinIO (MINIO_ROOT_USER/PASSWORD di
# docker-compose.yml) — nilai tidak pernah dicetak.
#
# Usage: bash scripts/minio-bucket.sh [nama-bucket]   (default: local)

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BUCKET="${1:-local}"
SAIL="./vendor/bin/sail"

if [ ! -x "$SAIL" ]; then
    echo "✗ $SAIL tidak ditemukan / tidak executable." >&2
    exit 1
fi

# Alias memakai kredensial dev di dalam container (tidak dicetak).
# `mc alias set` ulang bersifat idempoten (menimpa alias yang sama).
if ! "$SAIL" exec -T minio sh -c \
    'mc alias set myminio http://localhost:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" >/dev/null 2>&1'; then
    echo "✗ Gagal set alias MinIO (container minio belum berjalan? jalankan: ./vendor/bin/sail up -d --wait)." >&2
    exit 1
fi

# mc mb -p: tidak gagal bila bucket sudah ada.
if ! "$SAIL" exec -T minio mc mb -p "myminio/${BUCKET}" >/dev/null 2>&1; then
    echo "✗ Gagal membuat/memastikan bucket '${BUCKET}'." >&2
    exit 1
fi

"$SAIL" exec -T minio mc anonymous set download "myminio/${BUCKET}" >/dev/null 2>&1 || true

echo "✅ Bucket '${BUCKET}' siap (idempoten)."
