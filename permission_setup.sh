#!/bin/bash

# Required by composer install

mkdir -p "storage/framework"
chmod -R ugo+rw "storage/framework"
chown -R 1337:1000 "storage/framework"

mkdir -p "storage/logs"
chmod -R ugo+rw "storage/logs"
chown -R 1337:1000 "storage/logs"

# Required by npm install

mkdir -p "node_modules"
chmod -R ugo+rw "node_modules"
chown -R 1337:1000 "node_modules"

chmod ugo+rw "package-lock.json"
chown 1337:1000 "package-lock.json"

# Required by wayfinder

rm -rf "resources/js/actions"
mkdir -p "resources/js/actions"
chmod -R ugo+rw "resources/js/actions"
chown -R 1337:1000 "resources/js/actions"

rm -rf "resources/js/routes"
mkdir -p "resources/js/routes"
chmod -R ugo+rw "resources/js/routes"
chown -R 1337:1000 "resources/js/routes"

rm -rf "resources/js/wayfinder"
mkdir -p "resources/js/wayfinder"
chmod -R ugo+rw "resources/js/wayfinder"
chown -R 1337:1000 "resources/js/wayfinder"

# Required by eslint
chmod -R ugo+rw "resources/js"
chown -R 1337:1000 "resources/js"

# Required by vite

mkdir -p "public"
chmod -R ugo+rw "public"
chown -R 1337:1000 "public"

# Required by cache

mkdir -p "bootstrap/cache"
chmod -R ugo+rw "bootstrap/cache"
chown -R 1337:1000 "bootstrap/cache"

# Required by filesystem

mkdir -p "storage/app/public"
chmod -R ugo+rw "storage/app/public"
chown -R 1337:1000 "storage/app/public"

# Required by playwright e2e test

rm -rf "e2e"
mkdir -p "e2e"
chmod -R ugo+rw "e2e"
chown -R 1337:1000 "e2e"
