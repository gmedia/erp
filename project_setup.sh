#!/bin/bash

# Installing composer packages
composer install

# Installing npm packages
npm install

# Starting Laravel Sail
./vendor/bin/sail down -v
./vendor/bin/sail up -d --build

# Waiting for the database to be ready
sleep 60

# Running migrations
./vendor/bin/sail artisan migrate:fresh --seed

# Create symbolic link for storage
./vendor/bin/sail run rm -rf public/storage
./vendor/bin/sail artisan storage:link

# Generate ide helper
./vendor/bin/sail artisan ide-helper:generate

# Running Vite server
./vendor/bin/sail npm run dev
