#!/bin/bash

# Required by composer install

mkdir -p "storage/framework"
chmod -R ugo+rw "storage/framework"

mkdir -p "storage/logs"
chmod -R ugo+rw "storage/logs"

# Required by npm install

mkdir -p "node_modules"
chmod -R ugo+rw "node_modules"

chmod ugo+rw "package-lock.json"

# Required by wayfinder

mkdir -p "resources/js/actions"
chmod -R ugo+rw "resources/js/actions"

mkdir -p "resources/js/routes"
chmod -R ugo+rw "resources/js/routes"

mkdir -p "resources/js/wayfinder"
chmod -R ugo+rw "resources/js/wayfinder"

# Required by vite

mkdir -p "public"
chmod -R ugo+rw "public"

# Required by cache

mkdir -p "bootstrap/cache"
chmod -R ugo+rw "bootstrap/cache"

# Required by playwright

mkdir -p "playwright-report"
chmod -R ugo+rw "playwright-report"

mkdir -p "test-results"
chmod -R ugo+rw "test-results"
