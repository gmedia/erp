#!/usr/bin/env bash
# sync-agents.sh — Generate local configs for Kilo Code and OpenCode.ai
# from the .github/ source (GitHub Copilot config = source of truth).
#
# Usage:
#   bash scripts/sync-agents.sh          # sync both
#   bash scripts/sync-agents.sh kilo     # sync Kilo only
#   bash scripts/sync-agents.sh opencode # sync OpenCode only
#
# Both .kilo/ and opencode.json are gitignored.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

TARGET="${1:-all}"

sync_kilo() {
  echo "🔄 Syncing Kilo Code (.kilo/)..."
  bash .kilo/sync-from-github.sh
  echo "   ✅ Kilo sync complete"
}

sync_opencode() {
  echo "🔄 Syncing OpenCode.ai (opencode.json)..."
  bash scripts/sync-opencode.sh
  echo "   ✅ OpenCode sync complete"
}

case "$TARGET" in
  kilo)
    sync_kilo
    ;;
  opencode)
    sync_opencode
    ;;
  all|*)
    sync_kilo
    echo ""
    sync_opencode
    echo ""
    echo "🎉 All agent configs synced from .github/ source"
    ;;
esac
