# Social Login — Deployment Guide

Panduan ini untuk admin/ops saat mengaktifkan login Google Workspace dan Microsoft 365 pada instalasi ERP. **Tidak ada domain, tenant, atau nama perusahaan yang hardcode** — semua diisi oleh admin di UI atau lewat `.env`.

---

## 1. Daftarkan OAuth client

### Google Cloud Console
1. Buat / pilih project → **APIs & Services → Credentials**.
2. Buat **OAuth client ID**, application type: **Web application**.
3. Authorized redirect URI (exact match, tanpa trailing slash tambahan):
   ```
   https://<domain-instalasi>/api/social/google/callback
   ```
4. Salin **Client ID** dan **Client secret** ke `.env` (langkah 3).

### Microsoft Entra ID (Azure portal)
1. **App registrations → New registration**.
2. Supported account types: sesuaikan kebijakan instalasi. Untuk M365 saja, pilih *Accounts in this organizational directory only*.
3. **Authentication → Add a platform → Web**, redirect URI:
   ```
   https://<domain-instalasi>/api/social/microsoft/callback
   ```
4. Salin **Application (client) ID**, **Directory (tenant) ID**, dan client secret ke `.env`.

> Redirect URI harus **exact match** dengan yang dipanggil aplikasi. Perbedaan `http`/`https`, port, atau trailing slash akan menyebabkan login ditolak provider.

---

## 2. Environment variables

Tambahkan ke `.env` tiap instalasi (lihat `.env.example`):

```dotenv
APP_URL=https://<domain-instalasi>
FRONTEND_URL=https://<domain-instalasi>

GOOGLE_CLIENT_ID=<client-id>
GOOGLE_CLIENT_SECRET=<client-secret>
GOOGLE_REDIRECT_URI="${APP_URL}/api/social/google/callback"

MICROSOFT_CLIENT_ID=<client-id>
MICROSOFT_CLIENT_SECRET=<client-secret>
MICROSOFT_REDIRECT_URI="${APP_URL}/api/social/microsoft/callback"
MICROSOFT_TENANT_ID=common
```

- `MICROSOFT_TENANT_ID=common` menerima MSA dan M365 pada level Identity Platform. **Kebijakan akhir tetap ditentukan allowlist tenant di UI admin.**
- Untuk membatasi ke satu tenant sejak awal, isi dengan tenant ID organisasi.
- Jangan pernah commit client secret ke repository.

Setelah mengubah `.env`:
```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan config:cache
```

---

## 3. Konfigurasi admin (WAJIB)

Masuk sebagai admin → **Admin Settings → Authentication**.

| Pengaturan | Fungsi | Default |
|---|---|---|
| Enable social login | Aktif/nonaktif tombol login social | aktif |
| Enable password login | Aktif/nonaktif form email-password | aktif |
| Allow personal accounts | Izinkan Gmail pribadi / MSA pribadi | **mati** |
| Allowed email domains | Daftar domain Workspace yang boleh login, dipisah koma | **kosong** |
| Allowed Microsoft tenants | Daftar tenant ID M365 yang boleh login | **kosong** |
| Auto-link by email | Otomatis menautkan profil social ke user yang emailnya sama | mati |
| Auto-create users | Buat user saat login pertama (tetap butuh provisioning HR) | mati |

> **Penting:** allowlist kosong = **semua login social ditolak** (fail-closed). Isi domain/tenant sebelum mengaktifkan fitur ke user.

---

## 4. Alur login

1. User membuka `/login`, melihat tombol **Continue with Google** / **Continue with Microsoft**.
2. Jika `password_login_enabled = false`, form password disembunyikan.
3. User diarahkan ke provider, lalu kembali ke `/api/social/{provider}/callback`.
4. Backend memvalidasi state + PKCE + kebijakan, lalu redirect ke:
   ```
   /auth/social/callback#ticket=<single-use-ticket>
   ```
5. Halaman callback menukar tiket via `POST /api/social/exchange` dengan header `X-Social-Ticket-Token`, menyimpan Sanctum token, lalu masuk dashboard.
6. Jika user memiliki 2FA aktif, alur melewati `/two-factor-challenge?social_ticket=...` terlebih dahulu.

Token **tidak pernah** dikirim lewat query string.

---

## 5. Troubleshooting

| Error di UI | Penyebab | Tindakan |
|---|---|---|
| `Social login is disabled` | Toggle social login mati | Aktifkan di Settings → Authentication |
| `Your email domain is not allowed` | Domain `hd` tidak masuk allowlist | Tambahkan domain di Settings |
| `Your organization is not allowed` | Tenant ID tidak masuk allowlist | Tambahkan tenant ID di Settings |
| `Personal accounts are not allowed` | User memakai akun pribadi | Aktifkan toggle personal atau gunakan akun Workspace/M365 |
| `Your account is not registered` | User belum dibuat admin & JIT mati | Buat user dari modul Employees, lalu link manual dari Settings saat login dengan password |
| `The login session is invalid or has expired` | State kedaluarsa / tiket > 60 detik | Ulangi login |
| `The login provider could not be reached` | Redirect URI tidak cocok / secret salah | Periksa konsol provider & `.env` |

Untuk investigasi, lihat channel log `security` (provider, account_type, kode error, IP — tanpa token mentah).

---

## 6. Operasional

- **Cleanup tiket**: scheduler menghapus tiket login yang expired setiap 5 menit. Pastikan cron Laravel jalan:
  ```bash
  * * * * * cd /path/to/erp && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Rotasi secret**: perbarui di konsol provider dan `.env`, lalu `config:cache`.
- **Audit**: keputusan auth dicatat di log `security`; gunakan untuk menelusuri percobaan login yang ditolak.

---

## 7. Referensi

- Spesifikasi & keputusan desain: `docs/social-login-design.md`
- Aturan anti-hardcode brand: `.github/copilot-instructions.md` §10
- Laravel Socialite: <https://laravel.com/docs/12.x/socialite>
- Microsoft identity platform (tenant types): dokumentasi resmi Microsoft Entra
