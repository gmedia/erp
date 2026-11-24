#!/usr/bin/env bash
# Pasang Chromium untuk E2E di runner CI. apt (--with-deps) hanya dipakai bila
# Chromium tidak bisa dijalankan, karena mirror apt kadang sangat lambat.
set -euo pipefail

can_launch() {
    timeout 60 node -e "
        require('@playwright/test').chromium.launch()
            .then((browser) => browser.close())
            .catch((error) => { console.error(error.message); process.exit(1); });
    "
}

if timeout 180 npx playwright install chromium && can_launch; then
    echo "Chromium siap tanpa apt."
    exit 0
fi

echo "Chromium belum bisa dijalankan; memasang dependensi sistem lewat apt."
echo 'Acquire::Retries "10";' | sudo tee /etc/apt/apt.conf.d/80-retries

for attempt in 1 2 3; do
    if npx playwright install --with-deps chromium && can_launch; then
        exit 0
    fi
    echo "Playwright browser install failed (attempt ${attempt}/3). Retrying in 15s..."
    sudo apt-get clean || true
    sleep 15
done

echo "::error::Playwright browser install failed after 3 attempts."
exit 1
