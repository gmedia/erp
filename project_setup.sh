#!/bin/bash

# Pasang pre-push guard untuk koordinasi multi-server (non-fatal:
# jangan gagalkan setup bila dijalankan di luar clone git).
git config core.hooksPath .githooks 2>/dev/null || true

# Installing composer packages
composer install

# Installing npm packages
npm install

# .env (aman untuk host ber-IP publik: HOST_IP=127.0.0.1, port bentrok dipindah).
# Tidak menimpa .env yang sudah ada.
if [ -f scripts/setup-env.sh ]; then
    bash scripts/setup-env.sh
fi

# Image app: pakai image CI yang sudah dipublish (Dockerfile sama: docker/8.4)
# agar tidak build lokal >25 menit. Build lokal hanya bila SAIL_BUILD=1 atau pull gagal.
SAIL_IMAGE="sail-8.4/app"
CI_IMAGE="ghcr.io/gmedia/erp/ci-sail:8.4"
UP_ARGS=(-d --wait)
if [ "${SAIL_BUILD:-0}" = "1" ]; then
    UP_ARGS+=(--build)
elif docker pull "$CI_IMAGE"; then
    docker tag "$CI_IMAGE" "$SAIL_IMAGE"
else
    echo "Pull $CI_IMAGE gagal — build image lokal."
    UP_ARGS+=(--build)
fi

# Starting Laravel Sail (down -v menghapus volume/data lama — lihat AGENTS.md 3d)
./vendor/bin/sail down -v
./vendor/bin/sail up "${UP_ARGS[@]}"

# APP_KEY kosong di .env.example → generate sekali
if ! grep -qE '^APP_KEY=.+' .env; then
    ./vendor/bin/sail artisan key:generate
fi

# Running migrations
./vendor/bin/sail artisan migrate:fresh --seed

# Configure MinIO and create bucket (idempoten; lihat scripts/minio-bucket.sh)
echo "Configuring MinIO bucket..."
bash scripts/minio-bucket.sh local

# Create symbolic link for storage
./vendor/bin/sail run rm -rf public/storage
./vendor/bin/sail artisan storage:link

# Generate ide helper
./vendor/bin/sail artisan ide-helper:generate

# Running Vite server
./vendor/bin/sail npm run dev
