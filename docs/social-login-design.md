# Social Login Design — Google & Microsoft (Personal + Workspace/365)

**Status:** Rancangan (belum diimplementasikan)
**Dibuat:** 2026-11-18
**Scope:** Login OAuth untuk 4 varian akun — Google pribadi, Google Workspace, Microsoft pribadi, Microsoft 365.
**Arsitektur target:** Laravel 12 JSON API + React 19 SPA, Sanctum Bearer Token (tanpa Inertia, tanpa session login).

---

## 1. Ringkasan Keputusan

| Aspek | Keputusan |
|---|---|
| Provider OAuth | 2 provider (`google`, `microsoft`), 4 varian akun dibedakan dari klaim yang dikembalikan provider |
| Deteksi varian | Google: klaim `hd` → Workspace, tanpa `hd` → pribadi. Microsoft: `tid`/`tenant` → M365, `null` atau tid konsumen → pribadi **(hanya diizinkan jika admin aktifkan)** |
| Mode pendaftaran | **Invite-only ketat** — user HARUS sudah dibuat admin. `auto_link_by_email` default `false`. JIT provisioning opsional & eksplisit |
| **Kontrol admin** | **Domain yang boleh login & aktif/nonaktif login email-password diatur admin di UI Settings → disimpan di tabel `settings` (bukan hardcode/di .env saja).** Nilai `.env` hanya default awal saat seed |
| Token aplikasi | Tetap Sanctum Personal Access Token, dikembalikan dari endpoint exchange (bukan dari URL callback) |
| State / CSRF | Signed state (HMAC + nonce di cache), dibangun via override `getCodeFields()` pada provider kustom — **BUKAN** `with(['state' => ...])` |
| PKCE | Wajib untuk SPA public flow. Provider kustom override `hasInvalidState()` (stateless) + `getCodeFields()` + `getTokenFields()`; `code_verifier` diambil dari cache berdasarkan `state` |
| Callback ke SPA | Backend 302 ke `/auth/social/callback#ticket=...` — **token tidak pernah muncul di query string** |
| Tiket login | Single-use (atomic compare-and-swap), TTL 60 detik, hash di DB, terikat IP + User-Agent + nonce; cleanup by scheduler |
| Linking akun | Auto-link **DINONAKTIFKAN** default. Link eksplisit hanya dari halaman settings, wajib login + konfirmasi password. Email baru tidak auto-link |
| Password | `users.password` dibuat nullable; user OAuth-only tidak bisa login password sampai/set password |
| 2FA | Jika user memiliki `two_factor_confirmed_at`, alur OAuth wajib melewati tantangan 2FA sebelum tiket diterbitkan |

---

## 2. Kondisi Existing Codebase (baseline)

| File | Fakta yang relevan |
|---|---|
| `routes/api.php:7-15` | Auth public: `POST /api/login`, `/forgot-password`, `/reset-password`; protected: `/logout`, `/me` |
| `app/Http/Controllers/Api/AuthController.php:28-47` | Login password → `Auth::attempt()` → `createToken($deviceName)` → `{ token, user }` |
| `app/Models/User.php:55-100` | `HasApiTokens`, `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`; `$fillable = ['name','email','password']`; cast `password => hashed` |
| `database/migrations/0001_01_01_000000_create_users_table.php:11-19` | `email` unique, `password` **NOT NULL** → perlu migrasi baru |
| `database/migrations/2025_09_22_092704_create_employees_table.php` | `employees.user_id` nullable; `salary` & `hire_date` NOT NULL → user baru tanpa employee tidak punya menu di `/api/me` |
| `config/services.php` | Belum ada entri `google` / `microsoft` |
| `composer.json` | Belum ada `laravel/socialite`, belum ada `socialiteproviders/microsoft` |
| `resources/js/pages/auth/login.tsx` | Form password, `axiosInstance.post('/api/login')`, `login(token, payload)`, redirect ke `location.state?.from ?? '/dashboard'` |
| `resources/js/contexts/auth-context.tsx:70-75` | `login(token, userData)` → simpan token di `localStorage`, lalu `refreshAuth()` |
| `config/fortify.php` | Fortify terpasang tapi alur login aplikasi sudah custom; **jangan** menambahkan route Fortify untuk social login |

Implikasi penting: karena tidak ada employee, `AuthController::me()` mengembalikan `menus: []` (`resolveMenus(null)` di `AuthController.php:204-208`). Jadi user baru wajib diprovision employee oleh admin sebelum bisa memakai aplikasi.

**Terverifikasi review:** `employees.salary` (decimal 10,2) dan `hire_date` (date) keduanya **NOT NULL tanpa default** (`2025_09_22_092704_create_employees_table.php:21-22`). Jadi opsi "JIT buat employee minimal" TIDAK bisa langsung dipakai — harus ada migrasi nullable + UI "menunggu provisioning HR". Lihat §5.2 dan §10.1.

---

## 3. Pemetaan 4 Varian Akun

### 3.1 Google

| Varian | Sinyal dari provider | Penanganan |
|---|---|---|
| Google pribadi | `hd` tidak ada / kosong; email umumnya `@gmail.com` | `account_type = personal` |
| Google Workspace | klaim `hd = domain` (authoritative) | `account_type = workspace`, simpan `hosted_domain = hd` |

- Scope: `openid`, `email`, `profile`.
- Parameter `hd` **tidak boleh dikirim** pada authorization request: Socialite akan menggabungkannya ke token exchange request tempat Google tidak menerimanya. Enforcement dilakukan **eksklusif di backend** setelah klaim `hd` terverifikasi terhadap daftar domain dari `settings` (lihat §3.3).
- `email_verified` harus `true`; jika tidak, tolak.
- Baca `hd` dari **ID token claims** (`$socialUser->user['hd']`), bukan dari `userinfo` standar. `GoogleProvider::userFromToken()` secara default hanya mengambil `userinfo` (tidak ada `hd`), jadi provider kustom wajib me-merge klaim ID token:
  ```php
  $claims = $this->getClaims(); // JWT payload dari token response
  $user->map([
      'hosted_domain' => $claims['hd'] ?? null,
      'email_verified' => $claims['email_verified'] ?? false,
  ]);
  ```
  **Tanpa ini, semua akun Workspace akan terdeteksi sebagai `personal` dan allowlist domain tidak pernah jalan.**

### 3.2 Microsoft

| Varian | Sinyal dari provider | Penanganan |
|---|---|---|
| Microsoft pribadi (MSA) | `tenant === null` ATAU `tid = 9188040d-6c67-4c5b-b112-36a304b66dad` (consumers tenant) | `account_type = personal` |
| Microsoft 365 (work/school) | `tid` / `tenant.id` terisi dan bukan consumers tenant | `account_type = microsoft_365`, simpan `tenant_id` |

**Domain/tenant bukan hardcode.** Daftar domain/tenant yang diizinkan berasal dari `settings` (diedit admin lewat UI Settings), `.env` hanya memberi nilai awal saat seeding. Semua contoh memakai placeholder netral (`example.com`, `tenant-contoh`) — lihat aturan anti-branding di `.github/copilot-instructions.md` §10.

- Pakai `socialiteproviders/microsoft` dengan:
  ```php
  'microsoft' => [
      'client_id' => env('MICROSOFT_CLIENT_ID'),
      'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
      'redirect' => env('MICROSOFT_REDIRECT_URI'),
      'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
      'include_tenant_info' => true,
  ],
  ```
- `common` wajib jika ingin menerima **kedua** varian. Jika `MICROSOFT_TENANT_ID` diisi dengan tenant ID konkret, hanya M365 tenant itu yang bisa login (varian pribadi otomatis tidak bisa).
- With `include_tenant_info => true`, baca tenant dari `$socialUser->tenant`. **Jangan** mengandalkan `userinfo` / `getClaims()` Microsof untuk `email_verified` — field itu tidak dijamin ada. Verifikasi via ID token claims (`emails` + `verified_primary_email`) atau anggap verified hanya jika provider mengembalikan `email_verified => true` eksplisit dari klaim; selain itu tolak.
- Evaluasi **domain/tenant + toggle akun personal setiap login**, bukan hanya saat first-link — dan tulis ulang `social_accounts.account_type`, `hosted_domain`, `tenant_id` jika berubah (misal tenant dihapus atau user pindah domain).
- Scope: `openid`, `email`, `profile`, `User.Read`; tambahkan `offline_access` hanya jika refresh token diperlukan.

### 3.3 Kebijakan akses (configurable)

`config/social_login.php` (baru) — **hanya default statis & konstanta teknis**. Kebijakan yang bisa diubah admin TIDAK dibaca langsung dari sini, melainkan dari tabel `settings` melalui helper `SocialLoginPolicy`:

```php
return [
    // Default awal untuk seeder settings (bisa ditimpa admin dari UI).
    'defaults' => [
        'enabled' => true,
        'jit_provisioning' => false,
        'auto_link_by_email' => false,
        'allow_personal_google' => true,
        'allow_personal_microsoft' => true,
        'allowed_domains' => [],      // kosong = tidak ada domain diizinkan
        'allowed_tenants' => [],      // kosong = tidak ada tenant diizinkan
        'password_login_enabled' => true,
    ],
    // Konstanta teknis — bukan kebijakan bisnis.
    'microsoft' => [
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
        'consumers_tenant_id' => '9188040d-6c67-4c5b-b112-36a304b66dad',
    ],
    'state_ttl_seconds' => 300,
    'ticket_ttl_seconds' => 60,
    'rate_limit' => [
        'redirect' => '10,1',   // 10 req / menit / IP
        'callback' => '30,1',
        'exchange' => '20,1',
    ],
];
```

### 3.3.1 Sumber kebenaran kebijakan = `settings` (admin)

Gunakan pola `Setting::get($key, $default)` yang sudah ada (`app/Models/Setting.php:54-63`, dipakai di `AuthController::resolveSharedSettings()`). **Jangan hardcode domain/tenant di kode, config statis, atau `.env` sebagai satu-satunya sumber.**

| Key `settings` | Group | Type | Default (dari `config/social_login.php`) | Diubah admin via |
|---|---|---|---|---|
| `social_login_enabled` | `authentication` | boolean | `true` | Settings → Autentikasi |
| `social_login_allowed_domains` | `authentication` | json | `[]` | Settings → Autentikasi (textarea/memilih domain) |
| `social_login_allowed_tenants` | `authentication` | json | `[]` | Settings → Autentikasi |
| `social_login_allow_personal` | `authentication` | boolean | `true` | Settings → Autentikasi |
| `social_login_auto_link_by_email` | `authentication` | boolean | `false` | Settings → Autentikasi (opt-in) |
| `social_login_jit_provisioning` | `authentication` | boolean | `false` | Settings → Autentikasi (opt-in) |
| **`password_login_enabled`** | `authentication` | boolean | `true` | Settings → Autentikasi (toggle aktif/nonaktif login email-password) |

Seeder: `database/seeders/AuthenticationSettingSeeder.php` — `firstOrCreate` untuk semua key di atas (idempoten, sesuai konvensi `database/AGENTS.md`). Nilai awal dibaca dari `config('social_login.defaults.*')`, bukan brand perusahaan mana pun.

Helper: `app/Services/SocialAuth/SocialLoginPolicy.php` — satu tempat baca/cast/validasi settings:

```php
public static function allowedDomains(): array;      // Setting::get('social_login_allowed_domains', [])
public static function allowedTenants(): array;
public static function passwordLoginEnabled(): bool;
public static function personalAccountsEnabled(): bool;
```

Cache: gunakan `Cache::remember('social_login_policy', now()->addMinutes(10), ...)`; invalidasi di `Setting::set()` (tambahkan `cache()->forget('social_login_policy')` di `app/Models/Setting.php:81`).

**Login email-password (`AuthController::login()`) wajib mengecek `SocialLoginPolicy::passwordLoginEnabled()` sebelum `Auth::attempt()`.** Jika nonaktif → 403 `password_login_disabled`. Frontend `login.tsx` menyembunyikan form password & menampilkan pesan dari `/api/me` (`passwordLoginEnabled`) ketika toggle mati. `GET /api/social/policy` (public) mengembalikan flag UI yang aman (tanpa daftar domain/tenant sensitif) agar SPA tahu tombol mana yang ditampilkan.

Aturan evaluasi di callback (urutan penting):

1. `social_login_enabled` dari `settings` aktif → kalau tidak, 403 `social_login_disabled`.
2. `state` valid & belum dipakai (HMAC + nonce di cache) → kalau tidak, 400.
3. `email_verified === true` dari klaim provider yang benar (lihat §3.1 / §3.2) → kalau tidak, tolak.
4. Deteksi `account_type` seperti tabel di atas.
5. Jika akun pribadi dan `social_login_allow_personal = false` → tolak `personal_accounts_disabled`.
6. Jika Workspace: `hd` harus ada dan **masuk `SocialLoginPolicy::allowedDomains()`**. Jika daftar kosong → tolak (fail-closed, tidak boleh mengizinkan semua domain).
7. Jika M365: `tid` harus masuk `SocialLoginPolicy::allowedTenants()`. Jika daftar kosong → tolak fail-closed.
8. Cari `social_accounts` by `(provider, provider_account_id)`. **Jika ketemu, evaluasi ulang kebijakan 4-7 setiap login dan update kolom `account_type`/domain/tenant** → login.
9. Else cari `users` by email. **Default `social_login_auto_link_by_email = false` → JANGAN otomatis link.** Kembalikan `account_not_registered`; user harus login password dulu lalu link manual dari settings (dengan konfirmasi password). Opsi auto-link hanya boleh dinyalakan eksplisit oleh admin dan tetap wajib `email_verified`.
10. Else jika `social_login_jit_provisioning = false` → redirect ke SPA dengan `error=account_not_registered`.
11. Else buat user **tanpa employee** (lihat §5.2 Catatan C3), tandai `needs_hr_provisioning`, redirect ke halaman "menunggu provisioning HR" — **bukan** `/dashboard`.

> **Keputusan review:** invite-only adalah default yang benar. `auto_link_by_email = true` akan memberi akses ke menu/permission `employee` hanya karena email cocok — setara dengan privilege escalation. Matikan secara default dan jadikan fitur opt-in yang butuh konfirmasi password.

> **Keputusan user (2026-11-18):** admin mengatur daftar domain yang boleh login dan men-matikan/mengaktifkan login email-password lewat UI Settings (tersimpan di `settings`). Tidak ada nilai brand/domain hardcoded di source — selalu lewat `Setting` + placeholder netral. Fail-closed bila allowlist kosong.

### 3.4 2FA & akun OAuth-only

`User` memakai `TwoFactorAuthenticatable` (`app/Models/User.php:58`). Jika `two_factor_confirmed_at` terisi, OAuth **tidak boleh** langsung mengeluarkan token aplikasi. Alur:

1. Setelah policy §3.3 lolos, buat **tiket 2FA** (bukan tiket login) dengan `intent = two_factor`, `user_id`, TTL 60 detik.
2. Redirect ke `/two-factor-challenge?social_ticket=...`.
3. SPA POST kode 2FA ke endpoint verifikasi; jika valid → tukar tiket 2FA menjadi tiket login → exchange seperti biasa.

Jika user OAuth-only belum punya password, endpoint set-password wajib tersedia (§5.4) tapi tidak boleh dipakai untuk bypass 2FA.

---

## 4. Alur End-to-End

```
Browser (SPA)                Backend API                     Provider
     |                            |                              |
     | POST/GET /api/social/{provider}/redirect?intent=login     |
     |--------------------------->|                              |
     |                            | buat nonce + PKCE verifier   |
     |                            | simpan di cache (TTL 300s)   |
     |<---- { url, state } -------|                              |
     |                            |                              |
     | window.location.assign(url)|                              |
     |---------------------------------------------------------->|
     |                            |                    consent   |
     |<--------------------------- redirect ?code&state ---------|
     | GET /api/social/{provider}/callback?code&state            |
     |--------------------------->|                              |
     |                            | verify HMAC state + nonce    |
     |                            | tukar code (PKCE verifier) ->|
     |                            |<-------- access token -------|
     |                            | fetch profile + klaim        |
     |                            | evaluasi kebijakan (§3.3)    |
     |                            | buat tiket sekali pakai      |
     |<--- 302 /auth/social/callback#ticket=xxx -----------------|
     |                            |                              |
     | POST /api/social/exchange { ticket }                      |
     |--------------------------->|                              |
     |                            | validasi hash, TTL, IP/UA    |
     |                            | tandai consumed (atomic)     |
     |                            | createToken()                |
     |<---- { token, user } ------|                              |
     | login(token, payload) -> refreshAuth() -> /dashboard      |
```

**Mengapa tiket?** Mengembalikan Sanctum token lewat query string callback akan bocor ke log server, browser history, dan `Referer`. Fragment (`#`) tidak dikirim ke server, dan tiket ditukar lewat POST yang bisa diamankan.

---

## 5. Perubahan Backend

### 5.1 Dependency

```bash
sail composer require laravel/socialite socialiteproviders/microsoft
```

- Daftarkan listener Microsoft di `AppServiceProvider::boot()` (Laravel 11+ style):
  ```php
  Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
      $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
  });
  ```
- Untuk PKCE + Google, buat provider kustom:
  - `app/Services/SocialAuth/Providers/GoogleProvider.php` extends `Laravel\Socialite\Two\GoogleProvider`
  - `app/Services/SocialAuth/Providers/MicrosoftProvider.php` extends `SocialiteProviders\Microsoft\Provider`
  - **Override `hasInvalidState(): bool` → `return false;`** — jalur API tidak memakai `StartSession` (`bootstrap/app.php` mendaftarkan middleware `api` only), jadi `session()->pull('state')` Socialite akan selalu null dan menolak semua callback. Validasi HMAC + nonce dilakukan sendiri di `OAuthStateManager`.
  - **Override `getCodeFields($state = null): array`** → sisipkan `'code_challenge' => ..., 'code_challenge_method' => 'S256', 'state' => $signedState`. **Jangan** memakai `with(['state' => ...])` karena akan ikut ter-merge ke `getTokenFields()` saat exchange.
  - **Override `getTokenFields($code): array`** → ambil `code_verifier` dari cache berdasarkan `state` yang diverifikasi, lalu tambahkan `'code_verifier' => $verifier`.
  - Google: override `userFromToken()` untuk me-merge `hd` & `email_verified` dari ID token claims (lihat §3.1).
  - Daftarkan via `Socialite::extend('google', fn () => ...)` dan `Socialite::extend('microsoft', fn () => ...)` di `AppServiceProvider`. **(HANYA daftarkan `microsoft` via `Socialite::extend`; listener `SocialiteWasCalled` untuk microsoft tidak boleh didaftarkan dua kali.)**

### 5.2 Migrasi baru

File: `database/migrations/2026_11_18_000001_create_social_accounts_table.php`

```php
Schema::create('social_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('provider', 20);            // google | microsoft
    $table->string('provider_account_id');     // sub dari IdP
    $table->string('account_type', 30);        // personal | workspace | microsoft_365
    $table->string('email');
    $table->string('name')->nullable();
    $table->string('avatar_url')->nullable();
    $table->string('hosted_domain')->nullable();
    $table->string('tenant_id')->nullable();
    $table->text('access_token')->nullable();  // encrypted
    $table->text('refresh_token')->nullable(); // encrypted
    $table->timestamp('token_expires_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();

    $table->unique(['provider', 'provider_account_id']);
    $table->index(['user_id']);
    $table->index(['email']);
});
```

File: `database/migrations/2026_11_18_000002_create_social_login_tickets_table.php`

```php
Schema::create('social_login_tickets', function (Blueprint $table) {
    $table->id();
    $table->string('token_hash', 64)->unique();   // SHA-256 tiket mentah
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('state_nonce', 64)->unique(); // satu nonce = satu tiket
    $table->string('provider', 20);              // google | microsoft
    $table->string('account_type', 30);
    $table->string('ip_hash', 64);
    $table->string('user_agent_hash', 64);
    $table->timestamp('expires_at');
    $table->timestamp('consumed_at')->nullable();
    $table->timestamps();

    $table->index(['state_nonce', 'consumed_at']);
    $table->index(['expires_at']); // untuk cleanup scheduler
});
```

File: `database/migrations/2026_11_18_000003_make_users_password_nullable.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('password')->nullable()->change();
});
```

File: `database/migrations/2026_11_18_000004_make_employee_profile_fields_nullable.php`

```php
// WAJIB untuk JIT. Tanpa ini, `Employee::create()` gagal:
// salary & hire_date NOT NULL tanpa default di create_employees_table.php:21-22.
Schema::table('employees', function (Blueprint $table) {
    $table->decimal('salary', 10, 2)->nullable()->change();
    $table->date('hire_date')->nullable()->change();
});
```

> **Catatan C3 — JIT tetap tidak boleh langsung aktif:** meski kolom di-nullable, user tanpa employee tetap `menus: []` (`AuthController::me()` → `resolveMenus(null)`). Jika `jit_provisioning = true`, arahkan user ke halaman **"Menunggu provisioning HR"**, bukan `/dashboard`. Tandai dengan kolom `needs_hr_provisioning` di `users` (migrasi terpisah) atau cukup cek `employee === null` di `/api/me`.

> Laravel 12 native schema change mendukung `->change()` untuk MySQL/Postgres. Verifikasi dengan `sail artisan migrate` di lingkungan lokal.

**Cleanup scheduler** (`routes/console.php`): hapus tiket dengan `expires_at < now()->subDay()` setiap 5 menit. Tanpa ini tabel tumbuh tanpa batas.

### 5.3 Model & Action

| File | Tujuan |
|---|---|
| `app/Models/SocialAccount.php` | `$fillable`, cast `access_token`/`refresh_token` => `encrypted:array` (atau `encrypted` jika plain string), relasi `belongsTo(User::class)` |
| `app/Models/SocialLoginTicket.php` | Helper `consume(string $token, Request $request): ?self`: **atomic** `UPDATE ... SET consumed_at = now() WHERE token_hash = ? AND consumed_at IS NULL AND expires_at > now()` lalu cek `affectedRows === 1`. Validasi `ip_hash` + `user_agent_hash` + `state_nonce` cocok. Jangan SELECT lalu UPDATE (race condition). Jangan simpan raw token. |
| `app/Actions/SocialAuth/ResolveSocialUserAction.php` | Inti kebijakan §3.3; return `User` atau throw `SocialAuthException` dengan kode error |
| `app/Actions/SocialAuth/DetectAccountTypeAction.php` | Pure function: `(provider, claims) => account_type` + domain/tenant |
| `app/Actions/SocialAuth/CreateSocialLoginTicketAction.php` | Buat tiket, hash, simpan |
| `app/Services/SocialAuth/OAuthStateManager.php` | Sign/verify HMAC state, kelola nonce + PKCE verifier di cache |
| `app/Exceptions/SocialAuthException.php` | Kode error stabil: `invalid_state`, `email_unverified`, `personal_accounts_disabled`, `domain_not_allowed`, `tenant_not_allowed`, `account_not_registered`, `ticket_expired`, `ticket_consumed` |

Controller: `app/Http/Controllers/Api/SocialAuthController.php` — tipis, delegate ke Action (patuh konvensi `app/AGENTS.md`).

Helper: `app/Services/SocialAuth/SocialLoginPolicy.php` — baca/cast/cache settings (§3.3.1). Dipakai `AuthController`, `ResolveSocialUserAction`, dan endpoint `/api/social/policy`.

### 5.4 Endpoint

Semua di `routes/api.php` bagian **public** (sebelum `auth:sanctum`), kecuali link/unlink.

| Method | Path | Auth | Fungsi |
|---|---|---|---|
| `GET` | `/api/social/policy` | public | Flag UI aman: `socialLoginEnabled`, `passwordLoginEnabled`, `personalAccountsEnabled`, `providers: ['google','microsoft']`. **Tidak** mengirim daftar domain/tenant |
| `GET` | `/api/social/{provider}/redirect` | public | Validasi provider (`google|microsoft`), bikin state + PKCE, return `{ url, state }` |
| `GET` | `/api/social/{provider}/callback` | public | Verifikasi state, tukar code, resolve user, buat tiket, 302 ke SPA |
| `POST` | `/api/social/exchange` | public + `throttle` + `CSRF` (header `X-Social-Ticket-Token`) | Tukar tiket → `{ token, user }` (shape identik `/api/login`). Endpoint public raw rentan CSRF; tiket dari fragment + header custom wajib |
| `GET` | `/api/social/accounts` | `auth:sanctum` | Daftar akun tertaut milik user |
| `POST` | `/api/social/link/{provider}/redirect` | `auth:sanctum` | State ditandatangani dengan `user_id` + `intent=link`. Callback untuk `intent=link` **tidak boleh** dianggap SPA guest — arahkan ke halaman settings (bukan `/auth/social/callback`) agar `GuestRoute` tidak redirect ke dashboard |
| `DELETE` | `/api/social/accounts/{socialAccount}` | `auth:sanctum` | Unlink; tolak jika ini kredensial terakhir dan `password` null |
| `POST` | `/api/social/set-password` | `auth:sanctum` | Set password untuk user OAuth-only (validasi `confirmed|min:8`) — gunakan satu helper dengan `AuthController::resetPassword()` untuk menghindari duplikasi logika hash |
| `POST` | `/api/social/two-factor/verify` | public + tiket 2FA | Verifikasi kode 2FA untuk user OAuth yang punya 2FA aktif (§3.4) |

Parameter redirect: `intent=login|link`, opsional `account_type=personal|workspace|microsoft_365` (hanya hint UI; enforcement tetap dari klaim).

Response error callback: 302 ke `{SPA_URL}/auth/social/callback#error={code}&provider={provider}` — tidak pernah mengembalikan detail token/klaim ke browser.

### 5.5 Rate limiting & logging

- Terapkan `RateLimiter::for` di `app/Providers/AppServiceProvider.php`. Konfigurasi di `config/social_login.php` **hanya dibaca** — jangan lupa dipasang ke rute via `->middleware('throttle:social-redirect')` dll. (Review menemukan konfigurasi sebelumnya hanya deklaratif tanpa pemasangan.)
- Log ke channel `security` (tambahkan di `config/logging.php` jika belum ada): provider, account_type, user_id, hash email, IP, keputusan kebijakan. **Jangan** log access token / raw ticket.
- Kirim event ke Sentry untuk kegagalan verifikasi state (indikasi CSRF/replay).

---

## 6. Perubahan Frontend

| File | Perubahan |
|---|---|
| `resources/js/pages/auth/login.tsx` | Ambil flag dari `GET /api/social/policy`. Jika `passwordLoginEnabled = false`, **sembunyikan form email-password** dan tampilkan pesan netral. Jika `socialLoginEnabled = false`, sembunyikan 4 tombol. Tambah 4 tombol: **Google**, **Google Workspace**, **Microsoft**, **Microsoft 365** (tombol personal disembunyikan jika `personalAccountsEnabled = false`). Tombol Workspace/Microsoft 365 mengirim hint `account_type`; semua memanggil `GET /api/social/{provider}/redirect`, simpan `state` di `sessionStorage`, lalu `window.location.assign(url)`. Tombol ditaruh di atas/divider "atau login dengan password". |
| `resources/js/pages/auth/social-callback.tsx` (baru) | Baca `#ticket` / `#error`, POST `/api/social/exchange` dengan header `X-Social-Ticket-Token`, panggil `login(token, payload)`, arahkan ke `sessionStorage.getItem('social_intended')` atau `/dashboard`. Tangani error dengan pesan per kode di §5.3. |
| `resources/js/app-routes.tsx` | Daftarkan lazy route `/auth/social/callback` **di luar** `GuestRoute`. Jika tetap di dalam `GuestRoute` (lihat `components/guest-route.tsx`), user yang sudah login akan di-redirect ke dashboard dan callback link tidak pernah selesai. |
| `resources/js/pages/auth/two-factor-challenge.tsx` | Terima `?social_ticket=...`; POST ke `/api/social/two-factor/verify` sebelum exchange (§3.4). |
| `resources/js/pages/settings/social-accounts.tsx` (baru) | Daftar akun tertaut, tombol hubungkan provider lain, tombol unlink, form set password. Callback `intent=link` diarahkan ke sini. |
| `resources/js/pages/auth/pending-provisioning.tsx` (baru) | Halaman "Menunggu provisioning HR" untuk user JIT tanpa employee. |
| `resources/js/types/social-auth.ts` (baru) | Tipe `SocialProvider`, `SocialAccountType`, `SocialRedirectResponse`, `SocialAuthErrorCode`, `SocialPolicyResponse`. |
| `resources/js/pages/settings/authentication.tsx` (baru / perluasan settings existing) | UI admin: toggle social login, **toggle login email-password**, daftar domain/tenant yang diizinkan, toggle akun personal, auto-link & JIT. Simpan via endpoint settings yang sudah ada (`/api/admin-settings`) — **jangan** buat endpoint khusus hardcode. |
| `resources/js/lib/axios.ts` | Pastikan endpoint exchange tidak memakai interceptor yang otomatis menyuntik Bearer token lama. |

Semua UI baru wajib mengikuti `docs/ui-design-rules.md` (page shell, tombol, status/warna, error state). Semua file PHP baru wajib memakai import short class name (bukan FQCN `\App\...` di body) dan dijalankan dengan `sail bin duster fix` (lihat `AGENTS.md` §6).

Catatan UX: `state` disimpan di `sessionStorage` hanya sebagai pelengkap; source of truth ada di cache server. Simpan juga intended path sebelum redirect agar kembali ke halaman asal setelah login. Untuk alur link, simpan flag `social_intent=link` agar callback tidak dianggap login baru.

---

## 7. Keamanan — Checklist

- [x] `state` HMAC-signed + nonce sekali pakai di cache + TTL 5 menit, diverifikasi via override `hasInvalidState()` (stateless) → cegah CSRF & replay
- [x] PKCE S256 via `getCodeFields()` + `getTokenFields()` → cegah interception authorization code
- [x] Token aplikasi tidak pernah di query string / log; hanya lewat POST exchange
- [x] Tiket login single-use (atomic CAS), hash di DB, terikat IP + User-Agent + nonce, TTL 60 detik, cleanup scheduler
- [x] `email_verified` dari klaim provider yang benar wajib `true` (Google: ID token; Microsoft: klaim eksplisit)
- [x] Auto-link **default mati**; link manual butuh login + konfirmasi password
- [x] Kebijakan domain/tenant + toggle personal dievaluasi **setiap login**, bukan hanya saat link
- [x] 2FA aktif tidak bisa dilewati OAuth (tiket 2FA sebelum tiket login)
- [x] Allowlist domain Workspace & tenant M365; toggle akun personal
- [x] Redirect URI harus exact-match dengan konfigurasi provider (dikelola di `.env`, bukan hardcode)
- [x] Secrets hanya di `.env`, tidak di repo
- [x] Rate limit di redirect / callback / exchange **dipasang ke rute** (bukan hanya dikonfigurasi)
- [x] CSRF pada exchange via header custom + fragment ticket
- [x] Enkripsi access/refresh token di DB (cast `encrypted`)
- [x] Audit log keputusan auth tanpa data sensitif
- [ ] XSS: token tetap di `localStorage` mengikuti pola existing — dokumentasikan risikonya, jangan diubah di PR ini

---

## 8. Testing

### 8.1 Pest — `tests/Feature/SocialAuth/`

| Test | Ekspektasi |
|---|---|
| `redirect_test.php` | Response 200, `url` mengandung `state`, `code_challenge`, scope benar, dan **tidak** mengandung `hd` / `code_verifier` |
| `google_personal_callback_test.php` | Tanpa `hd` di ID token → `account_type = personal`, login berhasil |
| `google_workspace_callback_test.php` | ID token `hd = example.com` → `account_type = workspace`, `hosted_domain` tersimpan (buktikan merge klaim ID token bekerja) |
| `password_login_disabled_test.php` | Toggle `password_login_enabled = false` → `POST /api/login` 403; UI sembunyikan form |
| `policy_endpoint_test.php` | `/api/social/policy` hanya flag aman, tidak mengandung daftar domain/tenant |
| `admin_domain_setting_test.php` | Ubah `social_login_allowed_domains` via `Setting::set` → callback menghormati nilai baru tanpa deploy |
| `google_domain_not_allowed_test.php` | `hd = example.com` di luar allowlist → error `domain_not_allowed`, tiket tidak dibuat |
| `microsoft_personal_callback_test.php` | `tenant = null` / consumers tid → `personal` |
| `microsoft_365_callback_test.php` | `tid` terisi → `microsoft_365`, `tenant_id` tersimpan |
| `email_unverified_test.php` | Tolak dengan `email_unverified` (termasuk kasus Microsoft tanpa klaim eksplisit) |
| `invalid_state_test.php` | State salah / kadaluarsa / sudah dipakai → 400; pastikan tidak bergantung pada session |
| `ticket_replay_test.php` | Exchange kedua → 409 `ticket_consumed` (uji konkurensi, bukan hanya sequential) |
| `ticket_binding_test.php` | IP / User-Agent / nonce berbeda → tolak |
| `auto_link_disabled_test.php` | Email sudah ada di `users` tapi `auto_link_by_email = false` → `account_not_registered`, user lama tidak tersentuh |
| `explicit_link_test.php` | Login password + link manual + konfirmasi password → `social_accounts` dibuat |
| `email_changed_after_link_test.php` | IdP ganti email → `social_accounts` tetap terikat by `provider_account_id`, email diperbarui, **tidak** otomatis pindah ke user lain |
| `multi_provider_same_user_test.php` | Google + Microsoft terhubung ke 1 user; unlink salah satu tidak mengunci user |
| `policy_reevaluated_each_login_test.php` | Domain/tenant dihapus dari allowlist → login berikutnya ditolak meski sudah ter-link |
| `two_factor_oauth_test.php` | User dengan `two_factor_confirmed_at` tidak mendapat token sebelum kode 2FA valid |
| `jit_disabled_test.php` | User baru → `account_not_registered` |
| `jit_provisioning_test.php` | JIT aktif → user dibuat, **tanpa employee**, diarahkan ke halaman pending provisioning; `/api/me` menus `[]` |
| `unlink_last_credential_test.php` | User OAuth-only tidak bisa unlink akun terakhir |
| `exchange_csrf_test.php` | POST exchange tanpa header `X-Social-Ticket-Token` → 403 |
| `state_nonce_cross_provider_test.php` | Nonce dari Google dipakai di callback Microsoft → ditolak |
| `ticket_cleanup_test.php` | Scheduler menghapus tiket expired (fake time) |

Mock dengan `Socialite::shouldReceive(...)` — jangan hitting provider sungguhan. Gunakan `Sanctum::actingAs($user)` untuk endpoint protected (**bukan** `actingAs()`). Group test: `->group('social-auth')`.

### 8.2 Playwright — `tests/e2e/social-login/`

- Intercept request ke `accounts.google.com` / `login.microsoftonline.com` via `page.route()` untuk simulasi consent.
- Skenario: 4 varian tombol → callback → dashboard; error domain not allowed; state tampered; user belum terdaftar.
- Pastikan `localStorage.api_token` terisi dan `/api/me` mengembalikan user yang benar.

---

## 9. Rencana Implementasi (bertahap)

| Wave | Isi | Estimasi |
|---|---|---|
| 1 | Dependency, config, migrasi, model, `OAuthStateManager`, `DetectAccountTypeAction` | 0.5 hari |
| 2 | `SocialLoginPolicy`, seeder settings, `ResolveSocialUserAction`, `SocialAuthController`, endpoint public + exchange + `/policy`, `AuthController::login` cek toggle password, rate limit, logging | 1.5 hari |
| 3 | Frontend: 4 tombol di `login.tsx`, `social-callback.tsx`, route, tipe | 0.5 hari |
| 4 | Settings UI (domain/tenant + toggle password + opsi social) + social accounts + set password + 2FA challenge | 1 hari |
| 5 | Pest + E2E, duster fix, phpstan, types, CI | 1 hari |

Satu branch, satu MR: `feat/issue-N-social-login`. Handoff MR memakai template di `AGENTS.md`.

---

## 10. Asumsi & Keputusan yang Perlu Dikonfirmasi

1. **Keputusan user SUDAH DIAMBIL:** domain/tenant yang boleh login **diatur admin lewat UI Settings** (tersimpan di `settings`, fail-closed bila kosong); login email-password **bisa diaktifkan/dinonaktifkan admin** lewat toggle yang sama. Tidak ada hardcode domain/brand. Lihat §3.3.1.
2. **Akun personal diizinkan?** — default diizinkan, tapi tetap bisa dimatikan admin dari Settings. Untuk ERP internal disarankan matikan. **(Catatan review: ini kontradiksi dengan rekomendasi invite-only — sebaiknya matikan sejak awal.)**
3. **Domain/tenant awal** — seeder memakai array kosong (fail-closed). Admin mengisi dari UI setelah instalasi. Tidak perlu hardcode domain perusahaan mana pun.
4. **Microsoft tenant** — apakah mau menerima MSA pribadi + M365 tenant tertentu sekaligus? Jika ya, `tenant = common` + allowlist tenant dari settings; jika hanya tenant internal, set `MICROSOFT_TENANT_ID` langsung di `.env`.
5. **Auto-link by email — SUDAH DIPUTUSKAN: MATI** (bisa di-opt-in admin). Link eksplisit saja (settings + konfirmasi password).
6. **Refresh token** — tidak diambil default. Aktifkan `offline_access` hanya jika ada kebutuhan akses Graph/Google API lanjutan.
7. **2FA** — perlu konfirmasi: apakah user ERP sudah wajib 2FA? Jika ya, endpoint `/api/social/two-factor/verify` masuk Wave 2, bukan menyusul.

---

## 12. Ringkasan Hasil Review Deep Agent (2026-11-18)

Review read-only oleh agent `deep-low` menghasilkan verdict **REJECTED** dengan 3 blocker CRITICAL. Semua telah diperbaiki di dokumen ini:

| ID | Temuan | Perbaikan di dokumen |
|---|---|---|
| C1 | `with(['state' => ...])` + session-state Socialite bentrok dengan API-only (`bootstrap/app.php` tanpa `StartSession`); `hd` ikut ter-merge ke token request Google | §1 (state via `getCodeFields()`), §3.1 (hd tidak dikirim), §5.1 (override `hasInvalidState()`, `getCodeFields()`, `getTokenFields()`) |
| C2 | `auto_link_by_email = true` mengubah "invite-only" jadi privilege escalation via email | §1, §3.3 langkah 8, §10.5 — default `false`, link manual + konfirmasi password |
| C3 | JIT mustahil: `employees.salary`/`hire_date` NOT NULL; `AuthController::me()` → `menus: []` | §2, §5.2 (migrasi nullable + halaman pending provisioning), §3.3 langkah 10 |

Temuan HIGH yang juga diadopsi: 2FA bypass (§3.4), `email_verified` Microsoft/Google dari klaim yang salah (§3.1/§3.2), evaluasi ulang kebijakan tiap login, tiket atomic + binding + cleanup scheduler, `GuestRoute` pada callback link, migrasi employee nullable, rate limit yang hanya deklaratif, factory OAuth-only, duplikasi set-password, CSRF exchange, FQCN/duster, dan 9 test gap baru di §8.1.

Status saat ini: **siap untuk diimplementasikan** setelah keputusan yang tersisa di §10 (akun personal, tenant Microsoft, 2FA) dikonfirmasi tim. Keputusan domain/tenant & toggle password SUDAH final (§10.1).

### 12.1 Keputusan User Tambahan (2026-11-18)

1. Admin bisa mengatur **domain yang boleh login** → tersimpan di `settings` (`social_login_allowed_domains`), diubah dari UI Settings, fail-closed bila kosong. Tidak ada hardcode domain/brand.
2. Admin bisa mengatur **aktif/nonaktif login email-password** → `settings.password_login_enabled`, dicek di `AuthController::login()` dan flag UI dari `/api/social/policy`.
3. **Aturan agent baru:** ERP ini untuk perusahaan lain (on premise) → dilarang hardcode brand/domain/tenant/logo di kode, UI, migrasi, seeder, test, docs generik. Aturan tertanam di `.github/copilot-instructions.md` §10 dan `AGENTS.md` Core Rule 0. Pengecualian hanya identifier infra repo (`gmedia/erp`, `gmedia_erp`). Contoh di dokumen ini sudah diganti ke placeholder netral (`example.com`).

---

## 11. Referensi

- Laravel 12 Socialite — <https://laravel.com/docs/12.x/socialite> (stateless, `with()`, scopes)
- Socialite Providers — Microsoft — <https://socialiteproviders.com/Microsoft/> (tenant types, `include_tenant_info`)
- MS Identity Platform tenant types — `common`, `organizations`, `consumers`, tenant ID
- Konvensi internal: `AGENTS.md`, `docs/development-patterns.md`, `docs/ui-design-rules.md`, `app/AGENTS.md`, `routes/AGENTS.md`, `database/AGENTS.md`
