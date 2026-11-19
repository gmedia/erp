#!/usr/bin/env bash
set -euo pipefail

if grep -RIn --include='*.ts' --include='*.tsx' 'z\.date(' resources/js; then
    echo "Date form fields must use z.string() with yyyy-MM-dd, not z.date()." >&2
    exit 1
fi
