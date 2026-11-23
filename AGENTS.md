# AGENT WORKFLOW

> Rules the agent MUST follow without exception. Violation = invalid session.

## Core Rules

0. **PRODUK ON-PREMISE / MULTI-PERUSAHAAN — JANGAN hardcode brand.** ERP ini dipasang di banyak perusahaan. Jangan menulis `'gmedia'`, domain, tenant, logo, atau nama korporat lain di kode/UI/migrasi/seeder/test/docs generik. Pakai `Setting::get('company_name')`, `Setting::get('company_logo_path')`, `config('app.name')`, atau `.env`. Contoh pakai placeholder netral (`example.com`, `perusahaan.contoh`). Detail lengkap: section **On-Premise / Multi-Perusahaan** di bawah. Pengecualian: identifier infra repo (`gmedia/erp`, `gmedia_erp`, remote git, URL issue) — itu bukan branding produk.
1. **NEVER wait for CI/CD.** Push and move on.
2. **One task = one branch** (`feat/*` or `fix/*`) = **one MR/PR.**
3. **Every MR must have a handoff** (what was done, files changed, next steps).
4. **Session start: process open MRs first** — merge if green, fix if red.
5. **Process budget (OpenCode stability) — HARD LIMIT.** Parallel tool/agent fan-out kills the host session. Follow **Tool & Process Concurrency** below with zero exceptions.
6. **Seeder data harus realistis (WAJIB).** Data demo tidak boleh menampilkan penanda seperti `sample`, `dummy`, `test`, `contoh`, atau `lorem` di field yang dilihat pengguna (kode, nama, tujuan, catatan, plat, SIM). Pakai format produksi (mis. `VL-2026-0001`, `B 1234 XYZ`, `Budi Santoso`). Seeder wajib idempoten, mencakup semua stage workflow, konsisten dengan source of truth (approval/pipeline), dan membersihkan data demo lama bila format kode berubah. Detail: `database/AGENTS.md` → **Seeder Data Rules (MANDATORY)**.
7. **Semua komponen UI wajib memakai shadcn-ui (WAJIB).** Gunakan primitives yang sudah ada di `resources/js/components/ui/*` (Button, Input, Select, Dialog, Tabs, DropdownMenu, Calendar, dll.) sebelum membuat elemen HTML mentah. Untuk kebutuhan khusus, buat wrapper di `components/common`/`components/vehicle-loans` yang tetap memakai primitives shadcn; jangan menambah UI library baru tanpa persetujuan. Contoh: date-time picker memakai `Input`, upload memakai `Button` + `Input`, bukan `<input type=file>` polos.
8. **Ikon sidebar/modul wajib terdaftar di icon-map (WAJIB).** Setiap menu di `database/seeders/MenuSeeder.php` — termasuk semua child menu — wajib memakai nama ikon Lucide yang valid dan **wajib** ditambahkan ke **dua tempat** di `resources/js/lib/icon-map.ts`: import dari `lucide-react` dan entry di `iconMap`. Jangan mengandalkan fallback `CircleHelp`; fallback hanya jaring pengaman agar sidebar tidak kosong. Saat menambah/mengubah menu, jalankan `npm run check:menu-icons` dan pastikan hasilnya `0 missing`; audit ikon adalah bagian dari Definition of Done, bukan task terpisah. Contoh benar: `CarFront`, `ClipboardPlus`, `ChartNoAxesCombined`, `ShieldCheck` untuk family Vehicle Loans.

## Tool & Process Concurrency (HARD — prevents OpenCode kill)

> OpenCode has repeatedly been killed when the agent fired many tools/subagents in one turn. Prefer **serial** work. Throughput is secondary to session survival.

### Hard limits (per assistant turn)

| Kind | Max per turn | Notes |
|------|--------------|-------|
| Total tool calls | **3** | Prefer **1–2**. Never “spray” reads/greps. |
| Background `task` / explore / librarian | **0 default** | Use only if user explicitly asks for parallel research. Max **1** if used. |
| `run_in_background=true` | **Avoid** | Prefer sync, single agent. |
| Simultaneous file reads | **1–2** | Do not batch 5–10 `Read` calls. |
| Simultaneous bash / git / gh | **1** | Chain with `&&` in one shell if sequential steps needed. |
| SonarCloud API (`curl`) | **1** | One call, then reason; next call only if needed. MCP servers are not used. |

### Required workflow

1. **One file at a time** for edits: read → edit → next file. Never parallel multi-file edit batches.
2. **One search at a time**: one `grep`/`glob`/`bash`, use results, then next search.
3. **No parallel subagent swarms** for “explore everything”. Main agent greps/reads directly for small scopes.
4. **After any kill / interrupt**: resume with **strict serial** mode (1 tool per step) until the task finishes.
5. **When in doubt**: fewer tools. Incomplete progress + alive session > full plan + dead OpenCode.

### Explicitly forbidden

- ❌ 5+ tool calls in a single assistant message
- ❌ Multiple `task(subagent_type="explore"|"librarian")` in parallel
- ❌ Parallel `Read` of many files “to save time”
- ❌ Parallel Sonar + git + file reads + explore agents together
- ❌ Spawning agents for work the main agent can do with one `grep`/`Read`

### Allowed exceptions (still keep total ≤ 3 tools)

- Two independent **tiny** reads of known paths (e.g. two line ranges already identified)
- One `git status`/`log` combined in a **single** bash script
- User explicitly orders parallel research (“fire explore agents”)

### Post-kill recovery (MANDATORY)

1. After OpenCode kill / interrupt: **strict serial** — **1 tool call per assistant turn** until the interrupted task is finished or stable.
2. Do **not** re-fire explore/librarian/oracle swarms to “catch up”.
3. Resume from claimed GitHub Issue (`gh issue view N --comments`, marker `<!-- checkpoint -->`; fallback bila gagal: `gh api repos/gmedia/erp/issues/N/comments --jq '.[] | .body'`) + `git status` only; re-apply unfinished edits **one file at a time**. `task.md` is deprecated.
4. Prefer completing a small shippable slice (commit/PR) over re-planning the whole theme.

### Why this exists

Host RAM/CPU is limited. Parallel `Read`/`Edit`/`task` batches have **killed OpenCode mid-T5** (and earlier sessions). Session survival > agent throughput.

### E2E: Playwright di host, Laravel tetap di Docker

> Aturan ini mengoreksi asumsi lama "semua command E2E wajib `sail ...`". **Benar hanya untuk PHP/Artisan. Playwright cukup dijalankan di host.**

- `playwright.config.ts` dan `tests/e2e/global-setup.ts` sudah mendukung runner terpisah. `PLAYWRIGHT_USE_SAIL=1` memaksa **npm dan artisan** lewat Sail — jangan diset kalau ingin Playwright di host.
- **Laravel/MariaDB/MinIO/Valkey tetap wajib di Sail**: `./vendor/bin/sail up -d --wait` + `migrate:fresh --seed` + MinIO bucket.
- **Playwright di host**: `npx playwright test ...` (atau `PLAYWRIGHT_WORKERS=N npx playwright test ...`). Host butuh Node 20 + npm; `node_modules/` sudah ada di repo.
- **Full suite lokal/server**: pakai **`bash scripts/e2e-full.sh`** (di luar CI). Script mengotomasi alur yang benar sekaligus — stop Vite dev + hapus `public/hot` + `npm run build`, `config:cache` (biar web server baca `DISABLE_RATE_LIMITING`; tanpa ini **429 massal**), watchdog yang cache-ulang `config.php` bila hilang, daftar direktori **sama dengan CI**, `--retries=1` 1 worker, lalu `config:clear` via `trap` (sukses/gagal/Ctrl-C) supaya Pest tetap memakai `.env.testing`. Argumen diteruskan ke Playwright:
  - `bash scripts/e2e-full.sh` → seluruh direktori E2E (CI list)
  - `bash scripts/e2e-full.sh tests/e2e/branches` → hanya modul tertentu
  - `bash scripts/e2e-full.sh --shard=1/4 --reporter=line` → shard seperti CI
  - Exit code = exit Playwright; `bootstrap/cache/config.php` dijamin tidak tersisa.
- Supaya `global-setup` tetap menjalankan Artisan **di dalam container**, set `PLAYWRIGHT_PHP_BINARY` ke wrapper:
  `#!/usr/bin/env bash; exec ./vendor/bin/sail php "$@"` (simpan di `scripts/sail-php`, chmod +x). Tanpa ini, `tryLocalPhpBinary()` akan memakai `php` host dan gagal terkoneksi ke DB.
- `PLAYWRIGHT_BASE_URL` tetap `http://localhost:82` (port publish Sail), bukan `localhost:80` di dalam container.
- **Jangan** menjalankan `./vendor/bin/sail npx playwright test ...` untuk mengukur performa E2E: itu menambah overlay container dan bukan konfigurasi yang dipakai CI (CI menjalankan `npx playwright test` langsung di runner).
- `DISABLE_RATE_LIMITING=true` **harus aktif di proses web server di dalam container**, bukan hanya di Artisan CLI. Tanpa ini, E2E gagal dengan `429 Too Many Requests` (limiter `throttle:api` 60/menit). Cara aman tanpa mengubah `.env`: compose override yang menambah environment variable ke service `laravel.test`, lalu `config:cache` sekali supaya request HTTP ikut membaca nilai tersebut.
- `APP_ADMIN` yang dipakai `global-setup.ts` harus sama dengan admin hasil seed di container (CI pakai `admin@example.com`; host bisa berbeda). Jangan mengubah `.env` — set `APP_ADMIN` di environment proses Playwright.
- Pre-flight wajib sebelum push: jalankan `npx playwright test --list` dengan daftar direktori `tests/e2e/...` dari step `Run Playwright E2E` di `tests.yml` (79 direktori termasuk `business-flows`). Saat ini **539 tests di 78 files**; `--list` tanpa argumen menghitung semua spec (695 tests di 92 files) — bukan angka yang dipakai CI.
- CI memecah E2E menjadi **4 shard** (`--shard=n/4`, matrix job). Saat menambah/menghapus test/direktori E2E, update angka di atas dan pastikan tiap shard tetap terisi.
- CI memakai **1 worker Playwright per shard**. `PLAYWRIGHT_WORKERS>1` adalah **jalan buntu**: flaky di bawah beban, dan menaikkan `PHP_CLI_SERVER_WORKERS` ke 8 **tetap gagal** (E2E hang >50m, `waitForResponse` timeout massal — lihat #219). Jangan ulangi eksperimen ini; konfigurasi stabil 4 shard × 1 worker.
- **`sail test --parallel` ditolak** di repo ini. Tanpa `RefreshDatabase` di base `tests/TestCase.php`, worker paralel bertabrakan di DB `testing` (`Table 'testing.*' doesn't exist`). Menambahkan `RefreshDatabase` global pun tidak menolong: uji lokal → 41 failed / 2129 passed, **483s (lebih lambat dari serial ~314s)** karena state eksternal (cache/storage) dibagi antar worker. Pertahankan Pest serial (lihat #162/#183).

## Workflow

```
1. rtk git checkout -b feat/<desc>   # from latest main
2. Incremental commits                # feat:, fix:, refactor:, test:
3. rtk git push && gh pr create       # with handoff description
4. Move to next task immediately      # DO NOT wait for CI
```

> Use `rtk` wrapper for all git operations. `rtk` ensures the correct environment and SSH keys are configured. Never use raw `git` directly — always `rtk git <command>`.

### `rtk` tidak tersedia? Pasang otomatis

Agent **wajib** memastikan `rtk` tersedia sebelum menjalankan operasi git apa pun. Deteksi dengan `command -v rtk`. Jika tidak ditemukan:

1. Pasang dari installer resmi (binary terverifikasi SHA-256):
   ```bash
   curl -fsSL https://raw.githubusercontent.com/rtk-ai/rtk/refs/heads/master/install.sh | sh
   ```
2. Installer menaruh binary di `~/.local/bin/rtk`. Pastikan PATH termuat:
   ```bash
   export PATH="$HOME/.local/bin:$PATH"
   ```
   Tambahkan `export PATH="$HOME/.local/bin:$PATH"` ke `~/.bashrc` jika belum ada, agar sesi berikutnya otomatis punya `rtk`.
3. Verifikasi: `rtk --version` (bukan `command not found`) dan `rtk git status` harus menampilkan branch.
4. Jangan pernah fallback ke raw `git` hanya karena `rtk` belum ada — pasang dulu. Gunakan raw `git` hanya jika installer gagal, dan laporkan ke user sebagai blocker.

> Catatan: ini adalah `rtk-ai/rtk` (Rust token killer), bukan paket `rtk` dari crates.io.

## Session Start Checklist

```bash
gh pr list --state open --assignee @me    # Find your pending MRs
gh issue list --assignee @me --repo gmedia/erp  # Active claimed work (task.md deprecated); cari label status:claimed
git config core.hooksPath .githooks             # Pasang pre-push guard (sekali per server/clone)
gh pr checks <id>                          # Check CI status
# CI green → squash merge → delete branch → pull main
gh pr merge <id> --squash --delete-branch && rtk git pull --ff-only
# CI red   → checkout branch → fix → push
gh pr view <id> --json headRefName         # Get branch name
```

### Saat diminta "setup local development"

Agent wajib melakukan langkah ini **sebelum/menyertakan** `bash project_setup.sh`:

1. `git config core.hooksPath .githooks` — pasang pre-push guard. `project_setup.sh` juga melakukan ini secara non-fatal, tapi agent tetap harus memverifikasi hasilnya: `git config --get core.hooksPath` harus mengembalikan `.githooks`.
2. `gh auth status` — pastikan akun tim (mis. `oppytut`), bukan akun sesaat.
3. `git config user.name` / `git config user.email` — harus terisi; kalau kosong, set dari identitas commit terakhir repo (`git log -1 --format='%an <%ae>'`).
3b. **`.env` hanya lewat `bash scripts/setup-env.sh`.** Agent **dilarang** membaca, meng-`cat`/`grep`, atau mengedit `.env` langsung (diblokir `deny` di `.claude/settings.json`). Script membuat `.env` dari `.env.example` tanpa menimpa yang sudah ada, memaksa `HOST_IP=127.0.0.1`, memindah port forward yang bentrok (mis. Valkey 6379 → 6380), `chmod 600`, dan hanya mencetak key non-rahasia. `APP_KEY` diisi via `./vendor/bin/sail artisan key:generate`. Butuh nilai lain di `.env`? Minta user mengubahnya sendiri.
3b2. **Pest & password DB:** `.env.testing` (tracked) tidak memuat password DB; `docker-compose.yml` meneruskan `DB_PASSWORD` dari `.env` ke env container `laravel.test` (env proses menang atas dotenv). Gejala `Access denied … (using password: NO)` saat `sail test` = container belum di-recreate: `./vendor/bin/sail up -d --wait`.
3c. **Host ber-IP publik:** `HOST_IP` wajib `127.0.0.1` — port yang dipublish Docker **melewati UFW**, jadi `HOST_IP` kosong = MariaDB/Valkey/MinIO/app terbuka ke internet. Setelah `sail up`, verifikasi: `docker ps --format '{{.Ports}}'` hanya berisi `127.0.0.1:`.
3d. `project_setup.sh` menjalankan `sail down -v` + `migrate:fresh --seed` (menghapus volume/data) dan `sail npm run dev` di foreground — di host yang sudah punya data, jalankan langkahnya satu per satu dan Vite di background.
4. **Akses dari laptop = SSH tunnel** (lihat **Akses dari laptop (SSH tunnel)** di bawah); jangan pernah bind ke `0.0.0.0` di host ber-IP-publik.
5. **Image app tidak perlu di-build lokal.** `project_setup.sh` mem-pull `ghcr.io/gmedia/erp/ci-sail:8.4` (public, Dockerfile sama `docker/8.4`, dibangun CI) lalu men-tag-nya `sail-8.4/app`; UID container menyesuaikan saat runtime (`start-container` → `usermod`). Build lokal (>25 menit) hanya bila `SAIL_BUILD=1` atau pull gagal. Script juga menjalankan `sail up -d --wait` (bukan `sleep`) dan `key:generate` bila `APP_KEY` kosong.

### Akses dari laptop (SSH tunnel)

Saat user minta akses ERP di host remote/server dari laptop, agent **wajib** memeriksa kondisi aktual host sebelum memberi perintah, dan tidak boleh menebak dari default:

1. **Port yang dipublish:** `docker ps --format '{{.Names}} {{.Ports}}'`. Ambil port app (`APP_PORT`, default `82`) dan port Vite (`VITE_PORT`, default `5173`; `scripts/setup-env.sh` bisa memindahkannya kalau bentrok, mis. `5175`). Semua harus `127.0.0.1:`.
2. **Mode aset:** `test -f public/hot && cat public/hot`.
   - **Tidak ada `public/hot`** berarti mode build (`public/build`). Cukup tunnel port app.
   - **Ada `public/hot`** berarti Vite dev aktif dan browser memuat aset dari URL di file itu. Port Vite **wajib** ikut di-tunnel dengan **nomor port lokal yang sama**. Kalau tidak, halaman blank.
   - Isi `public/hot` harus `http://localhost:<VITE_PORT>`. Kalau `http://0.0.0.0:...` (hasil `npm run dev -- --host`), browser menolak. Hentikan, lalu jalankan `./vendor/bin/sail npm run dev:tunnel` (`vite.tunnel.config.ts`: listen di container, origin/HMR `localhost:<VITE_PORT>`).
3. **Perintah untuk laptop** (port app lokal boleh diganti, port Vite tidak; MinIO `9000` opsional untuk foto/dokumen):
   ```bash
   ssh -N -L 8082:127.0.0.1:<APP_PORT> -L <VITE_PORT>:127.0.0.1:<VITE_PORT> -L 9000:127.0.0.1:9000 <user>@<host>
   # buka http://localhost:8082 ; tanpa Vite dev: hapus -L <VITE_PORT>...
   ```
4. **Verifikasi sebelum menjawab:** `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:<VITE_PORT>/@vite/client` → `200` (bila Vite dev aktif), dan HTML app (`curl -s http://127.0.0.1:<APP_PORT>/`) merujuk `http://localhost:<VITE_PORT>/…` atau `/build/…`.
5. **Hentikan Vite dev sebelum E2E/screenshot** (`./vendor/bin/sail exec laravel.test pkill -f vite`, `public/hot` ikut terhapus) supaya aset sama dengan CI (build).
6. Jangan menulis alamat/IP server sewaan atau privat ke repo, PR, atau issue. Pakai placeholder `<user>@<host>`.

Guard menolak push dari branch tanpa pola `<type>/issue-N-slug`, issue belum `status:claimed`, atau bukan di-assign ke akun `gh`. Ini pelengkap koordinasi di https://github.com/gmedia/erp/issues.

## Commit Prefixes

| Prefix | When |
|--------|------|
| `feat:` | New feature or behavior |
| `fix:` | Bug fix |
| `refactor:` | Code restructure, no behavior change |
| `test:` | Test additions or fixes |
| `docs:` | Documentation only |
| `chore:` | Build, CI, dependencies |

## MR Handoff Template

```
## What was done
- [brief bullet list of changes]

## Files changed
- `path/to/file` — [what and why]

## Verification
- [ ] sail test --group {slug}
- [ ] sail bin duster fix
- [ ] npm run types
- [ ] CI passing (gh pr checks)

## Next steps
- [optional follow-up]
```

## Anti-Patterns

- ❌ Working directly on `main`
- ❌ Polling CI or waiting for pipelines
- ❌ Multiple unrelated changes in one MR
- ❌ Empty MR descriptions
- ❌ Force-push or rebase shared branches
- ❌ Using raw `git` directly — always use `rtk git`
- ❌ **Multi-process fan-out** (many tools/agents in one turn) — kills OpenCode; see **Tool & Process Concurrency**

## Agent Tooling (OpenCode & Claude CLI — tanpa MCP)

Tool agent yang didukung: **OpenCode** dan **Claude CLI**. Keduanya membaca file yang sama:

| Apa | Lokasi | Dipakai oleh |
|-----|--------|--------------|
| Aturan | `AGENTS.md` (root + `app/`, `database/`, `routes/`, `tests/`, `resources/js/`, `.github/`) | OpenCode & Claude (native) |
| Skills | `.claude/skills/<name>/SKILL.md` (+ `resources/`, `scripts/`) | Claude (native), OpenCode via `opencode.json` (`skills.paths`) |
| Commands | `.claude/commands/<name>.md` → `/<name>` | Claude (native), OpenCode via `opencode.json` |
| Subagent | `.claude/agents/<name>.md` | Claude (native), OpenCode via `opencode.json` |
| Config OpenCode | `opencode.json` (gitignored) — generate: `bash scripts/sync-opencode.sh` | OpenCode |

- Ubah aturan/skill/command **hanya** di lokasi di atas, lalu jalankan `bash scripts/sync-opencode.sh` bila menambah/menghapus command atau agent.
- **MCP server tidak dipakai.** Gunakan padanan CLI:

| Kebutuhan | Command |
|-----------|---------|
| Schema DB | `./vendor/bin/sail artisan db:show` (ringkas) → `./vendor/bin/sail artisan db:table {table}` |
| Daftar route | `./vendor/bin/sail artisan route:list --path=api/{slug}` |
| Uji query/logic | `./vendor/bin/sail artisan tinker --execute='...'` |
| Log aplikasi | `tail -n 50 storage/logs/laravel.log` |
| Docs Laravel/package | Source versi terpasang di `vendor/` / `node_modules/`, lalu docs resmi versi terkait |
| Blast radius / pemakai symbol | `rg -n '{Symbol}' app routes resources/js tests database config` (cek juga string route, query key, nama file) |
| Validasi refactor | `./vendor/bin/sail bin phpstan analyze`, `npm run types`, `./vendor/bin/sail test --group {slug}` |
| Komponen UI Shadcn | `resources/js/components/ui/`; tambah baru: `npx shadcn@latest add {name}` |
| Quality gate | SonarCloud Web API via `curl` (lihat **Quality Gate**) |

- **Token efficiency:** mulai dari data ringkas (`db:show`, `rg -l`, `head`), baru baca detail yang relevan; potong output panjang dengan `head`/`tail -n`/`jq`.
- **Security:** jangan hardcode API key/token/secret di file repo; pakai `.env` / environment variable.

## Quality Gate

- Data quality gate diambil dari **SonarCloud Web API** (publik, tanpa token) via `curl`, atau dari check SonarCloud di PR (`gh pr checks <id>`).
- **Alur standar** (`S=https://sonarcloud.io/api`, `K=gmedia_erp`; tambahkan `&pullRequest=<id>` untuk analisis PR):
  1. Status quality gate: `curl -s "$S/qualitygates/project_status?projectKey=$K" | jq '.projectStatus.status'`
  2. Jika failed → metrik inti: `curl -s "$S/measures/component?component=$K&metricKeys=duplicated_lines,duplicated_blocks,duplicated_lines_density,ncloc,coverage,bugs,vulnerabilities,code_smells" | jq -c '.component.measures'`
  3. Tentukan prioritas berdasarkan metrik yang paling kritis.
  4. Duplikasi: `curl -s "$S/measures/component_tree?component=$K&metricKeys=duplicated_lines&qualifiers=FIL&s=metric&metricSort=duplicated_lines&asc=false&ps=20"` → detail per file: `curl -s "$S/duplications/show?key=$K:<path>"`.
  5. Coverage: `curl -s "$S/measures/component_tree?component=$K&metricKeys=coverage&qualifiers=FIL&s=metric&metricSort=coverage&asc=true&ps=20"` → tambah test.
  6. Issues: `curl -s "$S/issues/search?componentKeys=$K&impactSeverities=HIGH,BLOCKER&resolved=false&ps=50"` → fix critical issues.
- **JANGAN** berspekulasi tentang status quality gate tanpa data dari SonarCloud API / check PR.

### Sonar Refactor Protocol (Duplication)

Untuk refactor berbasis SonarCloud dengan target menurunkan duplikasi dan menjaga konsistensi style antar modul (command `/refactor-sonar`):

1. **Baseline WAJIB** — quality gate + `duplicated_lines`, `duplicated_blocks`, `duplicated_lines_density`, `ncloc`, `coverage`. Mulai dari ringkasan; detail file/cluster hanya untuk prioritas tertinggi.
2. **Eksekusi semi-besar terkontrol** — 4–8 file per wave dengan pola yang sama; utamakan extraction shared concern/trait/helper untuk pola berulang lintas modul setara.
3. **Guard konsistensi antar modul** — terapkan pola yang sama ke sibling module setara pada wave yang sama; pertahankan API contract (route, payload, response keys, query params). Untuk rename/move/split/merge atau extraction lintas file, petakan semua pemakai via `rg` sebelum patch pertama. Jika dipicu API package/framework, cek source versi terpasang di `vendor/`/`node_modules/`.
4. **Verifikasi & tracking** — test terfokus modul terdampak via Sail; update `docs/refactor-sonar-progress.md` tiap wave (baseline/delta, ringkasan, evidence test, snapshot Sonar).
5. **Anomali metrik** — jika `coverage`/metrik lain anomali (mis. `0.0`) namun test lokal lulus, catat sebagai anomali pipeline di progress doc dan verifikasi ulang pada snapshot berikutnya.

## Session Handoff & Continuity (Lintas Server)

- User menjalankan banyak server (tmux + OpenCode/Claude CLI) pada workspace yang sama. Continuity dijaga via **GitHub Issues** `gmedia/erp` (label `status:*`, komentar `<!-- claim -->` / `<!-- checkpoint -->`), bukan `task.md`.
- Mulai sesi: ikuti **Session Start Checklist**; jika belum ada klaim, jalankan `/claim-issue`; baca `<!-- checkpoint -->` terakhir di `gh issue view N --comments`; verifikasi baseline `rtk git rev-parse HEAD` + `rtk git status --short`.
- Sebelum menutup sesi (atau setelah wave signifikan), jalankan `/checkpoint-progress` dan tulis komentar issue berisi: `Last updated`, `Current milestone`, `What changed in this session`, `Validated commands and outcomes`, `Open risks/blockers`, `Recommended next step`, plus hash commit terbaru.
- Command handoff: `/claim-issue`, `/continue-progress`, `/checkpoint-progress`, `/finish-claimed-issue`.
- Simpan ringkasan terstruktur, bukan log mentah panjang. Bahasa checkpoint: English ringkas atau Bahasa Indonesia.
- Peran dokumen: GitHub Issues = status aktif; `task.md`, `task.changelog.md`, `task.handoff-archive.md` = arsip (deprecated).

## On-Premise / Multi-Perusahaan — DILARANG hardcode brand

ERP ini dipasang di banyak perusahaan berbeda (on premise). Kode, UI, dan dokumentasi produk harus netral terhadap organisasi mana pun.

1. **Jangan hardcode nama brand, nama perusahaan, domain, tenant, logo, atau warna korporat** di kode, view/UI, migrasi, seeder, factory, config, test, atau dokumen generik.
   - ❌ `'gmedia'`, `'gmedia.co.id'`, `'Gmedia ERP'`, email `@gmedia.*`, tenant ID tertentu, aset logo bermerek.
   - ✅ Ambil dari data: `Setting::get('company_name')`, `Setting::get('company_logo_path')` (pola di `AuthController::resolveSharedSettings()`), `config('app.name')`, atau `.env`.
   - ✅ Contoh di dokumen/test pakai placeholder netral: `example.com`, `perusahaan.contoh`, `tenant-contoh`.
2. **Default di `.env.example`, seeder, dan config harus generik** (`APP_NAME=ERP`, placeholder domain).
3. **Sebelum commit**, periksa file yang diubah untuk string brand yang tidak disengaja; ganti dengan `Setting`/config/env.
4. **Perilaku produk tidak boleh bergantung pada identitas organisasi tertentu** — pakai flag/konfigurasi (mis. allowlist domain dari `settings`).

**Pengecualian** (bukan branding produk): identifier infrastruktur repo — org GitHub `gmedia/erp`, project key SonarCloud `gmedia_erp`, remote git, URL issue tracker, nama CI image, path filesistem bersejarah — dan file arsip/jurnal lama.

---

# PROJECT KNOWLEDGE BASE

**Generated:** 2026-11-09
**Commit:** 446c9498
**Branch:** main

## OVERVIEW

ERP system (gmedia) — Laravel 12 JSON API backend + React 19 full SPA frontend. Manages finance, assets, inventory, purchasing, approvals, and pipelines across 60+ modules.

## STRUCTURE

```
erp/
├── app/                # Backend: Actions, Domain, DTOs, Exports, Http, Models, Services
├── resources/js/       # Frontend SPA: React + TypeScript + Shadcn UI + TanStack Query
├── routes/api/         # 63 modular route files (auto-included by routes/api.php)
├── database/           # Migrations (106), Factories (88), Seeders (29)
├── tests/              # Pest (Feature/Unit) + Playwright E2E (106 specs)
├── docs/               # Design docs, module registry, 35 user guides
├── .claude/            # Agent skills, commands, subagents (OpenCode + Claude CLI)
├── .github/            # CI workflows, issue templates
├── .gitlab/            # GitLab CI deploy include (.gitlab-ci.yml)
├── scripts/            # Sync scripts, E2E lock helper
├── docker/             # Docker configs
└── task.md             # Deprecated handoff (active status: gmedia/erp/issues)
```

## WHERE TO LOOK

| Task | Location | Notes |
|------|----------|-------|
| Add new module (backend) | `app/Actions/{Module}/`, `app/Http/Controllers/`, `routes/api/` | Follow `docs/development-patterns.md` |
| Add new module (frontend) | `resources/js/pages/{slug}/`, `resources/js/components/{slug}/` | Use `createEntityCrudPage` pattern; ikuti `docs/ui-design-rules.md` |
| Register route (frontend) | `resources/js/app-routes.tsx` | Lazy-loaded `<Route>` |
| Register route (backend) | `routes/api/{module-slug}.php` | Auto-included |
| Add export | `app/Exports/` | Declarative `columns()` pattern |
| Add report | `app/Actions/Reports/`, `resources/js/pages/reports/` | `ReportDataTablePage` shell |
| Shared UI components | `resources/js/components/common/`, `resources/js/components/ui/` | Shadcn + custom |
| Shared backend traits | `app/Actions/Concerns/`, `app/Http/Controllers/Concerns/` | `InteractsWithExportFilters`, `LoadsResourceRelations` |
| Entity configs | `resources/js/utils/entityConfigs.ts` | Simple vs Complex config |
| Form schemas | `resources/js/utils/schemas.ts` (or per-module) | Zod validation |
| Type definitions | `resources/js/types/` | 46 type files |
| Hooks | `resources/js/hooks/` | 30 hooks; `useEntityForm`, `useCrudQuery`, `useCrudMutations` |
| User guide content | `docs/user-guide-*.md` | 35 guides served via `/api/user-guide` |
| User guide page | `resources/js/pages/user-guide/index.tsx` | Renders markdown from API |
| Module metadata | `docs/module-registry.md` | Full E2E + Pest registry |
| Dev patterns | `docs/development-patterns.md` | Canonical implementation guide |
| UI design rules | `docs/ui-design-rules.md` | Aturan pembuatan + review/koreksi UI konsisten antar modul |
| Migrations/factories/seeders | `database/` | 106 / 88 / 29 — see `database/AGENTS.md` |
| Backend route files | `routes/api/` | 63 files, 406 defs — see `routes/AGENTS.md` |
| CI pipelines | `.github/workflows/`, `.gitlab/ci/` | Pest + Playwright + deploy |
| Session handoff | `gmedia/erp/issues` | Active claim + checkpoint (`task.md` deprecated) |
| Skills/templates | `.claude/skills/` | Scaffolding for new features |
| Agent commands | `.claude/commands/` | `/claim-issue`, `/create-feature`, `/refactor-sonar`, … |
| Subagents | `.claude/agents/` | `refactor-safe` |

## CODE MAP

Reference counts = files referencing the symbol (rg; no LSP server installed). High counts = central abstractions — change these carefully.

| Symbol | Type | Location | Refs | Role |
|--------|------|----------|------|------|
| `createEntityCrudPage` | factory fn | `resources/js/components/common/EntityCrudPage.tsx` | 45 | Builds 43 CRUD pages |
| `destroyModel` | controller helper | `app/Http/Controllers/Controller.php` | 33 | Shared `destroy()` response |
| `ApprovalFlow` | model | `app/Models/ApprovalFlow.php` | 32 | Approval definitions |
| `ReportDataTablePage` | React component | `resources/js/components/common/ReportDataTablePage.tsx` | 24 | Report page shell |
| `PipelineState` | model | `app/Models/PipelineState.php` | 22 | Pipeline state catalog |
| `StoresItemsInTransaction` | trait | `app/Http/Controllers/Concerns/StoresItemsInTransaction.php` | 17 | Nested item transactions |
| `PipelineEntityState` | model | `app/Models/PipelineEntityState.php` | 15 | Per-entity pipeline state |
| `ApprovalFlowStep` | model | `app/Models/ApprovalFlowStep.php` | 15 | Approval step definitions |
| `LoadsResourceRelations` | trait | `app/Http/Controllers/Concerns/LoadsResourceRelations.php` | 14 | `show()` eager loads |
| `AbstractReportIndexExport` | base export | `app/Exports/Concerns/AbstractReportIndexExport.php` | 13 | Report export base |
| `useEntityForm` | hook | `resources/js/hooks/useEntityForm.ts` | 12 | Form + Zod wiring |
| `SimpleCrudStoreRequest` | request base | `app/Http/Requests/SimpleCrudStoreRequest.php` | 11 | Name-only CRUD validation |

## CONVENTIONS

- **NO Inertia.js** — pure API + SPA. Never import `@inertiajs/react`.
- **Sanctum Bearer Token** — stateless auth, not session/cookies.
- **`routes/web.php`** — catch-all SPA only. NEVER add routes here.
- **Sail required** — all runtime commands via `./vendor/bin/sail <cmd>`.
- **Empty wrapper classes** — must have `// Intentionally empty...` comment body.
- **Import hygiene** — always `use` at top, never FQCN with `\` in body.
- **Duster fix** — run `sail bin duster fix` after PHP changes.
- **Module naming** — kebab-case for routes/slugs, PascalCase for classes.
- **Form prop** — always `entity` (never module-specific name).
- **Test groups** — `->group('{module-slug}')` kebab-case, single group only.
- **react-helmet-async** — aliased to local shim (`resources/js/lib/`), never re-add upstream dep.
- **Routing** — backend: new file `routes/api/{module-slug}.php` (auto-included); frontend: lazy `<Route path="/{module}" element={<P><Component /></P>} />` in `resources/js/app-routes.tsx`.
- **UI consistency** — read `docs/ui-design-rules.md` before creating/reviewing UI (page shell, toolbar, table, status colors, form, modal).
- **Sidebar icons** — every menu in `database/seeders/MenuSeeder.php` uses a Lucide icon registered in `resources/js/lib/icon-map.ts`; `npm run check:menu-icons` must report `0 missing` (fallback `CircleHelp` is not a substitute).
- **Theme colors** — no raw Tailwind palette classes (`text-red-500`, `bg-emerald-100`) in `resources/js`; use tokens (`text-success`, `bg-warning-soft`, `text-destructive-foreground`). `npm run check:raw-colors` runs in CI; exceptions only via its allowlist.
- **Empty wrapper body** — multiline with comment, e.g. `class ExportBranchRequest extends SimpleCrudExportRequest { // Intentionally empty. Behavior is inherited from the base class. }` (duster would otherwise collapse it to one line).

## ANTI-PATTERNS (THIS PROJECT)

- ❌ `Inertia::render()`, `@inertiajs/react` imports
- ❌ `actingAs($user)` in tests → use `Sanctum::actingAs($user)`
- ❌ `assertInertia()` → use `assertJson()`, `assertJsonStructure()`
- ❌ Routes in `routes/web.php`
- ❌ FQCN with leading `\` in PHP body code
- ❌ Empty class bodies without intent comment
- ❌ Redundant `'created_at' => 'datetime'` casts in models
- ❌ Running commands without `sail` prefix

## ARCHITECTURE PATTERNS

| Pattern | Backend | Frontend |
|---------|---------|----------|
| Simple CRUD | `SimpleCrud*` base classes | `createSimpleEntityConfig()` |
| Complex CRUD | Custom Request/Resource/Export | `createComplexEntityConfig()` + sibling files |
| Reports | `AbstractReportIndexExport` + Action | `ReportDataTablePage` |
| Transactions | `StoresItemsInTransaction` trait | `ItemFormDialog` + nested form |
| Exports | `columns()` declarative + `InteractsWithExportFilters` | Export button in toolbar |
| Pipelines | `Pipeline` + `PipelineState` + `PipelineTransition` + `PipelineEntityState` | `components/pipeline/EntityStateActions` |
| Approvals | `ApprovalFlow` + `ApprovalFlowStep` + `ApprovalRequest` | `pages/my-approvals/` + `components/approvals/` |

## COMMANDS

```bash
# Development
composer dev                          # Server + queue + pail + vite (concurrent)
sail npm run dev                      # Vite only

# Testing
sail test --group {module-slug}       # Pest by module
sail test                             # All Pest tests
npm run test:e2e                      # All Playwright E2E
npx playwright test tests/e2e/{slug}/ # E2E by module

# Quality
sail bin phpstan analyze              # Static analysis
sail bin duster fix                   # Code style (TLint + Pint + CS Fixer)
sail npm run lint                     # ESLint
sail npm run lint:check               # ESLint, no writes
sail npm run format                   # Prettier
sail npm run format:check             # Prettier, no writes
npm run types                         # TypeScript check

# Utilities
sail artisan ide-helper:generate      # Facade autocomplete
sail artisan ide-helper:models -RW    # Model PHPDoc
```

## NOTES

- **2,931 files** in tracked source (1,998 PHP, 479 TSX, 255 TS) — large codebase.
- **63 API route files** / **406 route definitions** — auto-included by `routes/api.php`.
- **89 models**, **106 migrations**, **88 factories**, **29 seeders** — near 1:1 model/factory ratio.
- **61 backend action modules**, **48 domain filter modules**, **24 DTO modules**.
- **63 frontend page dirs** (98 files), **57 component dirs**, **106 Playwright specs**.
- **104 Pest test groups** (`->group('...')`); 67 Feature dirs, 10 Unit dirs.
- **SonarCloud** integrated — quality gate, duplication, coverage tracked.
- **Sentry** for error monitoring.
- **Octane** for performance (production).
- **Scramble** for auto-generated API docs.
- **Zustand** used alongside React Query for client state.
- **`task.md`** is a deprecated handoff document — active status lives in `gmedia/erp/issues` (labels `status:*`, comments `<!-- claim -->` / `<!-- checkpoint -->`). Read it only for historical context.
- **`.claude/`** holds agent skills (scaffolding templates), commands, and subagents shared by OpenCode and Claude CLI.
