#!/bin/bash

# Required by composer install

mkdir -p "storage/framework"
chmod -R ugo+rw "storage/framework"
chown -R 1000:1000 "storage/framework"

mkdir -p "storage/logs"
chmod -R ugo+rw "storage/logs"
chown -R 1000:1000 "storage/logs"

# Required by npm install

mkdir -p "node_modules"
chmod -R ugo+rw "node_modules"
chown -R 1000:1000 "node_modules"

chmod ugo+rw "package-lock.json"
chown 1000:1000 "package-lock.json"

chmod ugo+rw "package.json"
chown 1000:1000 "package.json"

# Required by wayfinder

rm -rf "resources/js/actions"
mkdir -p "resources/js/actions"
chmod -R ugo+rw "resources/js/actions"
chown -R 1000:1000 "resources/js/actions"

rm -rf "resources/js/routes"
mkdir -p "resources/js/routes"
chmod -R ugo+rw "resources/js/routes"
chown -R 1000:1000 "resources/js/routes"

rm -rf "resources/js/wayfinder"
mkdir -p "resources/js/wayfinder"
chmod -R ugo+rw "resources/js/wayfinder"
chown -R 1000:1000 "resources/js/wayfinder"

# Required by eslint

chmod -R ugo+rw "resources/js"
chown -R 1000:1000 "resources/js"

# Required by vite

mkdir -p "public"
chmod -R ugo+rw "public"
chown -R 1000:1000 "public"

# Required by cache

mkdir -p "bootstrap/cache"
chmod -R ugo+rw "bootstrap/cache"
chown -R 1000:1000 "bootstrap/cache"

# Required by filesystem

mkdir -p "storage/app/public"
chmod -R ugo+rw "storage/app/public"
chown -R 1000:1000 "storage/app/public"

# Required by playwright e2e test

rm -rf "e2e"
mkdir -p "e2e"
chmod -R ugo+rw "e2e"
chown -R 1000:1000 "e2e"

# Required by test

rm -rf "coverage-html"
mkdir -p "coverage-html"
chmod -R ugo+rw "coverage-html"
chown -R 1000:1000 "coverage-html"

touch "coverage.xml"
chmod ugo+rw "coverage.xml"
chown 1000:1000 "coverage.xml"

rm -rf "vendor/pestphp/pest/.temp"

# Required by ide helper

touch "_ide_helper.php"
chmod ugo+rw "_ide_helper.php"
chown 1000:1000 "_ide_helper.php"

mkdir -p "app/Models"
chmod -R ugo+rw "app/Models"
chown -R 1000:1000 "app/Models"

# Required by duster

mkdir -p "app"
chmod -R ugo+rw "app"
chown -R 1000:1000 "app"

mkdir -p "tests"
chmod -R ugo+rw "tests"
chown -R 1000:1000 "tests"

# Required by Laravel

chmod -R ugo+rw "database"
chown -R 1000:1000 "database"

# Fix agent skill script permissions
for agent_dir in .agent .trae .kilocode .roo .cline .claude; do
    if [ -d "$agent_dir" ]; then
        chmod +x \
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
chmod ugo+rw .env
chown 1000:1000 .env
