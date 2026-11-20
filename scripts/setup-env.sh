#!/usr/bin/env bash
# setup-env.sh — Buat .env lokal yang aman dari .env.example.
#
# Satu-satunya jalur agent (OpenCode/Claude CLI) untuk membuat .env; agent tidak
# membaca/mengedit .env langsung. Script ini:
#   - tidak pernah menimpa .env yang sudah ada,
#   - memaksa HOST_IP=127.0.0.1 (port Docker melewati UFW di host ber-IP publik),
#   - memindah port forward yang sudah dipakai proses lain ke port kosong berikutnya,
#   - chmod 600, lalu hanya mencetak key non-rahasia (HOST_IP + port).
# APP_KEY diisi terpisah: ./vendor/bin/sail artisan key:generate
#
# Usage: bash scripts/setup-env.sh

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

ENV_FILE=".env"
EXAMPLE_FILE=".env.example"

# Variabel port yang dipublish docker-compose.yml beserta default-nya.
PORT_VARS=(
  "APP_PORT:80"
  "VITE_PORT:5173"
  "FORWARD_DB_PORT:3306"
  "FORWARD_VALKEY_PORT:6379"
  "FORWARD_MINIO_PORT:9000"
  "FORWARD_MINIO_CONSOLE_PORT:8900"
  "PLAYWRIGHT_PORT:9323"
  "COVERAGE_PORT:9300"
  "COVERAGE_NEW_PORT:9301"
)

print_summary() {
  echo "Ringkasan (key non-rahasia):"
  grep -E "^(HOST_IP|$(printf '%s|' "${PORT_VARS[@]%%:*}" | sed 's/|$//'))=" "$ENV_FILE" | sed 's/^/  /'
}

if [ -f "$ENV_FILE" ]; then
  echo "• $ENV_FILE sudah ada — tidak diubah."
  print_summary
  exit 0
fi

[ -f "$EXAMPLE_FILE" ] || { echo "✗ $EXAMPLE_FILE tidak ditemukan" >&2; exit 1; }

# Set KEY=VALUE: ganti baris yang ada, atau tambahkan di akhir.
set_env() {
  local key="$1" value="$2"
  if grep -qE "^${key}=" "$ENV_FILE"; then
    sed -i -E "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
  fi
}

get_env() {
  grep -E "^$1=" "$ENV_FILE" | tail -1 | cut -d= -f2- | tr -d '"' || true
}

port_busy() {
  ss -ltnH "sport = :$1" 2>/dev/null | grep -q .
}

umask 077
cp "$EXAMPLE_FILE" "$ENV_FILE"
chmod 600 "$ENV_FILE"

set_env HOST_IP 127.0.0.1

used_ports=" "
for entry in "${PORT_VARS[@]}"; do
  key="${entry%%:*}"
  default="${entry##*:}"
  port="$(get_env "$key")"
  port="${port:-$default}"
  original="$port"
  while port_busy "$port" || [[ "$used_ports" == *" $port "* ]]; do
    port=$((port + 1))
  done
  used_ports+="$port "
  if [ "$port" != "$original" ]; then
    echo "• $key: port $original sudah dipakai → $port"
  fi
  set_env "$key" "$port"
done

echo "✅ $ENV_FILE dibuat dari $EXAMPLE_FILE (chmod 600)."
print_summary
echo "Lanjut: ./vendor/bin/sail up -d --wait && ./vendor/bin/sail artisan key:generate"
