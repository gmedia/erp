#!/bin/bash

# Installing composer packages
composer install

# Installing npm packages
npm install

# Copying .env.example to .env
cp .env.example .env

# Create symbolic link for storage
rm -rf public/storage
php artisan storage:link
