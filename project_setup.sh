#!/bin/bash

# Installing composer packages
composer install

# Installing npm packages
npm install

# Copying .env.example to .env
cp .env.example .env
