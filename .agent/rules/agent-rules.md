---
description: Aturan agent untuk project ERP
---

# Agent Rules

1) Skills: selalu cek Skill yang tersedia; pakai jika relevan; jika tidak, tulis alasan singkat.
2) MCP: selalu cek MCP server/tools yang tersedia; prioritaskan untuk schema DB, routes, logs, docs, file ops, dan shadcn UI; jika tidak dipakai, tulis alasan singkat.
3) Sail: semua command terminal wajib via `./vendor/bin/sail <command>` (artisan/composer/npm/test). Jangan jalankan langsung di host.
