---
description: Aturan agent untuk project ERP
---

# Agent Rules

## 1. Stack Arsitektur

> **PENTING:** Project ini menggunakan arsitektur **Laravel API Backend + React Full SPA**. **TIDAK ADA Inertia.js.**

| Layer | Teknologi |
|-------|-----------|
| Backend API | Laravel (JSON API only) |
| Frontend SPA | React + TypeScript (standalone SPA, bukan Inertia) |
| Routing Frontend | `react-router-dom` → `resources/js/app-routes.tsx` |
| Routing Backend | `routes/api/*.php` (63 modular files) |
| Data Fetching | React Query (`useCrudQuery`, `useCrudMutations`, custom hooks) |
| Meta Tags | `Helmet` via import `react-helmet-async` (alias lokal ke `resources/js/lib/react-helmet-async.tsx`) |
| Autentikasi | Sanctum Bearer Token (stateless, bukan session/cookies) |
| State Management | React Context (`auth-context.tsx`) + React Query cache |
| Lazy Loading | Route-level code splitting di `app-routes.tsx` |

### Routing Convention

- **Backend**: Buat file route baru di `routes/api/{module-slug}.php`. File di-include otomatis oleh `routes/api.php`.
- **Frontend**: Register lazy-loaded route baru di `resources/js/app-routes.tsx` dengan pattern `<Route path="/{module}" element={<P><Component /></P>} />`.
- **Web.php**: `routes/web.php` hanya berisi catch-all SPA route → **JANGAN tambah route di sini**.

### Anti-Pattern (DILARANG!)

- ❌ **JANGAN** import dari `@inertiajs/react` (`Head`, `Link`, `router`, `usePage`)
- ❌ **JANGAN** gunakan `Inertia\Inertia` atau `Inertia::render()` di backend
- ❌ **JANGAN** gunakan `actingAs($user)` di feature test → gunakan `Sanctum::actingAs($user)`
- ❌ **JANGAN** gunakan `assertInertia()` di test → gunakan `assertJson()`, `assertJsonStructure()`
- ❌ **JANGAN** tambah route di `routes/web.php`
- ❌ **JANGAN** re-add dependency upstream `react-helmet-async` ke `package.json`; tetap gunakan import `react-helmet-async` yang sudah dialias ke shim lokal React 19

## 2. Skills & MCP

1) **Skills**: selalu cek Skill yang tersedia; pakai jika relevan; jika tidak, tulis alasan singkat.
2) **MCP**: selalu cek MCP server/tools yang tersedia; pilih server yang paling spesifik untuk task; jika tidak dipakai, tulis alasan singkat.
	- `laravel-boost`: prioritaskan untuk schema DB, routes, logs, docs Laravel ecosystem, dan tinker di project ini.
	- `context7`: wajib untuk docs library/framework/SDK/API/CLI yang butuh syntax, konfigurasi, migration, upgrade notes, version-specific behavior, atau debugging berbasis package docs. Mulai dengan `mcp_context7_resolve-library-id`, lalu `mcp_context7_query-docs` dengan query spesifik.
	- `depwire`: wajib untuk dependency graph, blast radius, impact analysis, health score, dead code, security scan, dan refactor aman lintas file atau symbol. Sebelum rename/move/delete/split/merge, jalankan `mcp_depwire_get_file_context(...)`, `mcp_depwire_impact_analysis(...)`, atau `mcp_depwire_simulate_change(...)` sesuai scope.

## 3. Sail

Semua command runtime project wajib via `./vendor/bin/sail <command>` (artisan/composer/npm/test). Command git (status/log/diff/commit) boleh dijalankan langsung di host.

## 4. Testing

| Tipe | Auth Pattern | Assertions |
|------|-------------|------------|
| Feature Test (PHP) | `Sanctum::actingAs($user)` | `assertJson()`, `assertJsonStructure()`, `assertOk()` |
| Unit Test (PHP) | Tidak perlu auth | Pest assertions |
| E2E (Playwright) | Bearer token injection ke localStorage | `waitForResponse('/api/...')` |

## 5. Empty Wrapper Class

- Untuk class PHP yang sengaja kosong dan hanya mewarisi behavior dari base CRUD class seperti `SimpleCrudIndexRequest`, `SimpleCrudExportRequest`, `SimpleCrudResource`, atau `SimpleCrudCollection`, **JANGAN** biarkan body class benar-benar kosong.
- Selalu pakai body multiline dan tambahkan komentar intent berikut:

```php
class ExportBranchRequest extends SimpleCrudExportRequest
{
	// Intentionally empty. Behavior is inherited from the base class.
}
```

- Alasan: `./vendor/bin/sail bin duster fix` dapat mengompak class kosong menjadi one-line class, yang membuat intent kurang jelas dan dapat memicu issue style/Sonar.

## 6. Import & FQCN Hygiene

- Untuk kode PHP executable seperti controller, action, request, model, factory, migration, seeder, dan test, **WAJIB** import dependency di bagian atas file lalu gunakan short class name di body code.
- **JANGAN** gunakan fully-qualified class name dengan leading backslash di body code seperti `\App\Models\User::factory()`, `\Laravel\Sanctum\Sanctum::actingAs(...)`, `\Illuminate\Validation\Rule::unique(...)`, `\Carbon\Carbon::setTestNow(...)`, atau `\Illuminate\Support\Facades\Storage::disk(...)`.
- FQCN tetap boleh dipakai di PHPDoc, generic annotations, dan `::class` metadata jika memang dibutuhkan.
- Setelah generate atau refactor file PHP, verifikasi dengan `./vendor/bin/sail bin duster fix` agar issue TLint semacam ini tidak lolos.

## 7. MCP Routing, Security & Token Efficiency

- **Routing (WAJIB)**:
	- Gunakan `laravel-boost` untuk schema DB, routes, browser/app logs, dan docs Laravel ecosystem yang sesuai versi project ini.
	- Gunakan `context7` untuk docs package umum/non-Laravel atau saat butuh referensi API, syntax, atau konfigurasi yang version-specific dan up-to-date.
	- Gunakan `depwire` untuk pertanyaan structural codebase seperti "siapa memakai file/symbol ini?", "apa yang rusak jika diubah?", atau "bagaimana health dependency graph saat ini?".

- **Security**:
	- Jangan hardcode API key/token/secrets di file repo (termasuk `.vscode/mcp.json`).
	- Gunakan input variable atau environment variable untuk credential MCP.

- **Token Efficiency (WAJIB)**:
	- Untuk schema DB, selalu mulai dari mode ringkas: `mcp_laravel-boost_database-schema(summary: true)` lalu filter table spesifik jika perlu.
	- Untuk docs search, gunakan query spesifik dan batasi package saat memungkinkan (`packages: [...]`).
	- Untuk Context7, resolve library ID sekali lalu query docs dengan kata kunci spesifik; hindari query generik dan jangan pakai lebih dari yang dibutuhkan.
	- Untuk Depwire, mulai dari target lokal seperti `mcp_depwire_get_architecture_summary()`, `mcp_depwire_get_file_context(...)`, atau `mcp_depwire_impact_analysis(...)` sebelum scan yang lebih lebar.
	- Untuk perubahan struktural, jalankan `mcp_depwire_simulate_change(...)` sebelum edit file; jangan mulai dari rename/move manual.
	- Gunakan `token_limit` secukupnya; mulai dari kecil lalu naikkan hanya jika hasil terpotong.
	- Jangan fetch log/error panjang tanpa kebutuhan; mulai dari jumlah entry kecil.
	- Saat butuh banyak data baca-only, prioritaskan pencarian terarah dulu (search/list), baru baca konten detail yang relevan saja.

## 8. Sonar MCP Refactor Protocol (Duplication)

Untuk task refactor berbasis SonarQube dengan target menurunkan duplikasi dan menjaga konsistensi style antar modul, ikuti protokol ini:

1. **Baseline WAJIB (MCP Sonar)**
	- Ambil `quality gate` + metrik inti: `duplicated_lines`, `duplicated_blocks`, `duplicated_lines_density`, `ncloc`, `coverage`.
	- Mulai dari ringkasan; detail file/cluster hanya untuk prioritas tertinggi.

2. **Eksekusi Semi-Besar Terkontrol**
	- Refactor 4-8 file per wave dengan pola yang sama (jangan campur banyak tipe refactor dalam satu wave).
	- Utamakan extraction shared concern/trait/helper untuk pola berulang lintas modul setara.

3. **Guard Konsistensi Style Antar Modul**
	- Saat satu modul dalam family diubah, terapkan pola style/struktur yang sama ke sibling module setara pada wave yang sama.
	- Pertahankan API contract (route, payload, response keys, query params) dan arsitektur API-only.
	- Untuk wave yang mencakup rename/move/split/merge atau extraction lintas file, jalankan Depwire lebih dulu untuk file context, impact analysis, atau simulate change sebelum patch pertama.
	- Jika pola refactor dipicu API package/framework, gunakan Context7 untuk memastikan abstraction mengikuti docs versi project/package terbaru.

4. **Verifikasi dan Tracking**
	- Jalankan test terfokus modul terdampak via Sail.
	- Update `docs/refactor-sonar-progress.md` setiap wave: baseline/delta, ringkasan perubahan, evidence test, snapshot Sonar.
	- Jika Depwire atau Context7 dipakai untuk keputusan wave, catat evidence ringkasnya di progress doc.

5. **Penanganan Anomali Metrik**
	- Jika `coverage`/metrik lain anomali (mis. `0.0`) namun test lokal lulus, catat sebagai anomali pipeline di progress doc dan lanjutkan verifikasi pada snapshot berikutnya.

## 9. Session Handoff & Continuity (Lintas Server)

- Karena user menjalankan banyak server berisi tmux + opencode pada workspace yang sama, agent **WAJIB** menjaga continuity via **GitHub Issues** (`gmedia/erp`), bukan file `task.md` (deprecated sejak 2026-11-17).
- **Sumber kebenaran aktif = https://github.com/gmedia/erp/issues** (label `status:*`, komentar `<!-- claim -->` / `<!-- checkpoint -->`).

### Aturan Wajib

1. Saat memulai sesi baru:
	- `gh pr list --state open --assignee @me --repo gmedia/erp` — selesaikan PR merah lebih dulu.
	- `gh issue list --assignee @me --repo gmedia/erp` — pilih yang berlabel `status:claimed`.
	- Jika belum ada klaim, jalankan `/claim-issue` sebelum mengerjakan apa pun.
	- Baca `gh issue view N --comments` → cari komentar `<!-- checkpoint -->` terakhir sebagai pengganti "Continuation Prompt".
	- Verifikasi baseline cepat: `git rev-parse HEAD`, `git status --short`.

1b. Saat diminta "setup local development", sebelum `bash project_setup.sh`:
	- `git config core.hooksPath .githooks` lalu verifikasi `git config --get core.hooksPath` = `.githooks`.
	- `gh auth status` harus akun tim (`oppytut`); `git config user.name` / `user.email` harus terisi.
	- Jangan bind aplikasi ke `0.0.0.0` di host ber-IP-publik; akses via SSH tunnel (`localhost:82`).

2. Sebelum menutup sesi (atau setelah wave perubahan signifikan), jalankan `/checkpoint-progress` dan tulis komentar issue berisi:
	- `Last updated`
	- `Current milestone`
	- `What changed in this session`
	- `Validated commands and outcomes`
	- `Open risks/blockers`
	- `Recommended next step`

3. Gunakan prompt reusable untuk handoff:
	- `.github/prompts/claim-issue.prompt.md`
	- `.github/prompts/continue-progress.prompt.md`
	- `.github/prompts/checkpoint-progress.prompt.md`

4. Jika ada commit, tulis hash commit terbaru pada checkpoint issue.

5. Jangan simpan log mentah panjang di komentar issue; simpan ringkasan terstruktur agar tetap terbaca.

6. Peran dokumen (semua `task.*` adalah arsip, bukan sumber aktif):
	- GitHub Issues = status handoff aktif, klaim, dan next action
	- `task.md` = deprecated (arsip status lama)
	- `task.changelog.md` = arsip changelog produk/fitur (deprecated)
	- `task.handoff-archive.md` = arsip checkpoint handoff E2E lama

7. Hindari duplikasi checkpoint historis; riwayat tersimpan otomatis sebagai komentar issue.

8. Jaga konsistensi bahasa:
	- Komentar checkpoint issue boleh English ringkas atau Bahasa Indonesia sesuai konteks tim.
