#!/bin/bash

# Required by composer install

set -e

OS_NAME="$(uname -s 2>/dev/null || echo unknown)"
IS_WINDOWS=false

case "$OS_NAME" in
    CYGWIN*|MINGW*|MSYS*)
        IS_WINDOWS=true
        ;;
esac

safe_chmod() {
    if command -v chmod >/dev/null 2>&1; then
        chmod "$@" 2>/dev/null || true
    fi
}

safe_chown() {
    if [ "$IS_WINDOWS" = false ] && command -v chown >/dev/null 2>&1; then
        chown "$@" 2>/dev/null || true
    fi
}

mkdir -p "storage/framework"
safe_chmod -R ugo+rw "storage/framework"
safe_chown -R 1000:1000 "storage/framework"

mkdir -p "storage/logs"
safe_chmod -R ugo+rw "storage/logs"
safe_chown -R 1000:1000 "storage/logs"

# Required by npm install

mkdir -p "node_modules"
safe_chmod -R ugo+rw "node_modules"
safe_chown -R 1000:1000 "node_modules"

safe_chmod ugo+rw "package-lock.json"
safe_chown 1000:1000 "package-lock.json"

safe_chmod ugo+rw "package.json"
safe_chown 1000:1000 "package.json"

# Required by wayfinder

rm -rf "resources/js/actions"
mkdir -p "resources/js/actions"
safe_chmod -R ugo+rw "resources/js/actions"
safe_chown -R 1000:1000 "resources/js/actions"

rm -rf "resources/js/routes"
mkdir -p "resources/js/routes"
safe_chmod -R ugo+rw "resources/js/routes"
safe_chown -R 1000:1000 "resources/js/routes"

rm -rf "resources/js/wayfinder"
mkdir -p "resources/js/wayfinder"
safe_chmod -R ugo+rw "resources/js/wayfinder"
safe_chown -R 1000:1000 "resources/js/wayfinder"

# Required by eslint

safe_chmod -R ugo+rw "resources/js"
safe_chown -R 1000:1000 "resources/js"

# Required by vite

mkdir -p "public"
safe_chmod -R ugo+rw "public"
safe_chown -R 1000:1000 "public"

# Required by cache

mkdir -p "bootstrap/cache"
safe_chmod -R ugo+rw "bootstrap/cache"
safe_chown -R 1000:1000 "bootstrap/cache"

# Required by filesystem

mkdir -p "storage/app/public"
safe_chmod -R ugo+rw "storage/app/public"
safe_chown -R 1000:1000 "storage/app/public"

# Required by playwright e2e test

rm -rf "e2e"
mkdir -p "e2e"
safe_chmod -R ugo+rw "e2e"
safe_chown -R 1000:1000 "e2e"

# Required by test

rm -rf "coverage-html"
mkdir -p "coverage-html"
safe_chmod -R ugo+rw "coverage-html"
safe_chown -R 1000:1000 "coverage-html"

touch "coverage.xml"
safe_chmod ugo+rw "coverage.xml"
safe_chown 1000:1000 "coverage.xml"

rm -rf "vendor/pestphp/pest/.temp"

# Required by ide helper

touch "_ide_helper.php"
safe_chmod ugo+rw "_ide_helper.php"
safe_chown 1000:1000 "_ide_helper.php"

mkdir -p "app/Models"
safe_chmod -R ugo+rw "app/Models"
safe_chown -R 1000:1000 "app/Models"

# Required by duster

mkdir -p "app"
safe_chmod -R ugo+rw "app"
safe_chown -R 1000:1000 "app"

mkdir -p "tests"
safe_chmod -R ugo+rw "tests"
safe_chown -R 1000:1000 "tests"

# Required by Laravel

safe_chmod -R ugo+rw "database"
safe_chown -R 1000:1000 "database"

# Fix agent skill script permissions
for agent_dir in .agent .trae .kilocode .roo .cline .claude; do
    if [ -d "$agent_dir" ]; then
        safe_chmod +x \
            "$agent_dir/skills/wizard.sh" \
            "$agent_dir/skills/feature-crud-simple/scripts/scaffold.sh" \
            "$agent_dir/skills/feature-crud-simple/scripts/generate.sh" \
            "$agent_dir/skills/feature-crud-complex/scripts/scaffold.sh" \
            "$agent_dir/skills/feature-crud-complex/scripts/generate.sh" \
            "$agent_dir/skills/refactor-backend/scripts/check-architecture.sh" \
            "$agent_dir/skills/refactor-backend/scripts/generate.sh"
    fi
done

# Copying .env.example to .env
cp .env.example .env
safe_chmod ugo+rw .env
safe_chown 1000:1000 .env
