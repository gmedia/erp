# Custom Agents

Folder ini berisi custom agent untuk task yang butuh routing lebih tegas daripada prompt biasa.

## Kapan Pakai Agent

| Agent | Gunakan Saat | Jangan Pakai Saat |
|-------|--------------|-------------------|
| `Safe Refactor` | rename, move, split, merge, delete, blast radius, impact analysis, refactor lintas file | typo lokal, styling kecil, edit satu file tanpa dependency risk |
| `Context7 Research` | syntax package, konfigurasi framework, migration guide, upgrade notes, API/CLI docs terbaru | logic bisnis internal repo, analisis dependensi codebase, refactor lokal |

## Routing Singkat

1. Jika pertanyaan utamanya adalah "bagaimana API/package ini dipakai sekarang?" gunakan `Context7 Research`.
2. Jika pertanyaan utamanya adalah "apa yang rusak jika saya ubah ini?" gunakan `Safe Refactor`.
3. Jika task butuh keduanya, mulai dari agent yang paling dekat dengan risiko utama, lalu lanjutkan dengan MCP yang tersisa pada agent utama atau workflow terkait.

## Hubungan dengan Prompt

- Gunakan prompt ketika Anda butuh workflow langkah demi langkah yang masih dikerjakan oleh agent utama.
- Gunakan custom agent ketika Anda ingin persona/toolset yang lebih sempit dan lebih terarah.
- Untuk refactor struktural, prompt `.github/prompts/refactor-safe-depwire.prompt.md` dan agent `Safe Refactor` saling melengkapi.
- Untuk lookup docs, prompt `.github/prompts/context7-docs.prompt.md` dan agent `Context7 Research` saling melengkapi.