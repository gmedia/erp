#!/bin/bash

# Installing composer packages
composer install

# Installing npm packages
npm install

# Copying .env.example to .env
cp .env.example .env

# Starting Laravel Sail
sail up -d

# Waiting for the database to be ready
sleep 60

# Running migrations
sail artisan migrate:fresh --seed

# Create symbolic link for storage
sail run rm -rf public/storage
sail artisan storage:link
