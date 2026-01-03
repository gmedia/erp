#!/bin/bash

# Installing composer packages
composer install

# Installing npm packages
npm install

# Copying .env.example to .env
cp .env.example .env

# Starting Laravel Sail
./vendor/bin/sail up -d

# Waiting for the database to be ready
sleep 60

# Running migrations
./vendor/bin/sail artisan migrate:fresh --seed

# Create symbolic link for storage
./vendor/bin/sail run rm -rf public/storage
./vendor/bin/sail artisan storage:link
