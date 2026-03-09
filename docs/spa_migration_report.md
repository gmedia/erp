# Summary Report: Migrasi Laravel + Inertia -> Laravel + React Full SPA

Laporan ini berisi ringkasan perubahan pada branch `main` yang unggul 44 commit dibandingkan `origin/main`. Fokus utama dari perubahan ini adalah transisi arsitektur dari monolith (Laravel + Inertia) menjadi arsitektur *Full Single Page Application* (SPA) menggunakan Laravel API murni di sisi backend dan React SPA di sisi frontend.

## 1. Perubahan Arsitektur & Teknologi Utama
- **Penghapusan Inertia.js:** Ketergantungan pada Inertia.js telah dihilangkan sepenuhnya baik dari komponen React maupun controller Laravel.
- **Routing Frontend (Client-side):** Beralih menggunakan `react-router-dom` untuk menangani navigasi antar halaman secara mandiri. File konfigurasi routing utama dipindahkan ke `resources/js/app-routes.tsx` beserta komponen pendukung seperti `protected-route.tsx` dan `guest-route.tsx`.
- **State Management & Data Fetching:** Migrasi penuh menggunakan **React Query** untuk *client-side data fetching* untuk berkomunikasi berbasia API (ditandai dengan refactor ke custom hooks seperti `useCrudQuery`, `useCrudMutations`, dll).
- **Meta Tags:** Penggantian komponen `<Head>` dari Inertia dengan penerapan library standar `react-helmet-async`.
- **Autentikasi API:** Backend beralih menggunakan stateles token authentication (Laravel Sanctum/Passport) berdasarkan modifikasi struktur database `personal_access_tokens` dan frontend yang kini menggunakan transmisi *Bearer Token* di *headers*, serta penghapusan auth *XSRF-Token cookies*.

## 2. Struktur API (Backend)
- **API Endpoint:** Pembentukan routing REST API spesifik di `routes/api.php` dan migrasi namespace URL prefix (banyak yang beralih menjadi `/api/*` serta penghapusan obsolete `/v1`).
- **Controller Refactoring:** Controller yang sebelumnya mengembalikan *Inertia Response* seperti `ReportController`, `AssetController`, dan *Auth Controller* telah direfaktor agar memberikan murni objek JSON API.
- **Export/Import Action:** Transisi fungsi export laporan yang di-handle di API (men-generate file eksport dan me-return URL via `Storage::disk('public')`) untuk fungsionalitas seperti *Stock Adjustments*, *Inventory Valuations*, dan *Stock Movements*.

## 3. Infrastruktur & Komponen Frontend
- **Setup Aplikasi Utama:** File titik mula React (`resources/js/app.tsx`) telah diamendmen menggunakan provider murni React dan Context API (`auth-context.tsx`). Server Side Rendering logic dari Inertia (`ssr.tsx`) telah dihapus.
- **Lazy Loading Routes:** Implementasi teknik *route level code-splitting* (lazy load) di semua halaman aplikasi guna merampingkan *initial load times* SPA.
- **Restrukturisasi Layout & Page:** File halaman di-refactor guna menjalankan alur data asinkron via loader secara native (misal pada komponen `ReportController` frontend).

## 4. Testing End-to-End (E2E) dan QA
- **Skenario API Playwright:** Tes E2E telah diselaraskan dengan mekanisme terbaru, yaitu menggunakan inject *Bearer token* ke _local storage_ browser pada proses *beforeEach* alih-alih melakukan manipulasi session XSRF.
- **Explicit API Waits:** Diperbaruinya berbagai *helpers* Playwright untuk secara eksplisit melakukan *await* terhadap response dari JSON endpoint backend guna mencegah instabilitas tes (contoh pada alur edit/import di `Inventory Stocktake` dan eksekusi filter `Admin Settings`).
- **Refactoring Feature Test (Backend):** Seluruh pengujian fitur (Pest/PHPUnit) telah diperbarui untuk mensimulasikan otentikasi API menggunakan Laravel Sanctum (`Sanctum::actingAs`), menggantikan basis sesi standar (`actingAs`). Asersi Inertia (`assertInertia`) diganti sepenuhnya dengan asersi JSON API (seperti `assertJson`, `assertJsonStructure`, `assertOk`) untuk memvalidasi API *responses* secara langsung.

---

## 5. Log Perubahan *(Commit History)*

Berikut adalah daftar Commit ID dan Pesan terkait migrasi ini:

- `aeffd17` update route
- `677c93e` chore: Add test run output and modify the Employee model unit test.
- `51b8149` feat: Remove Inertia.js dependency and related views, controllers, and middleware.
- `12b26f1` refactor: Rename `AppI18nProvider` and update its data source, alongside removing the `build:ssr` script.
- `ccbfe43` docs: Update SPA migration report with new commit count and details on backend feature test refactoring.
- `feb7c9d` feat: Add test output file and update various feature tests, a controller, and a request file.
- `377dd90` refactor: Update feature tests to use Sanctum for API authentication and JSON assertions instead of Inertia-specific checks.
- `077148f` Refactor authentication feature tests for SPA migration, updating password reset and email verification tests to use API endpoints and removing several UI-based authentication tests.
- `b4e179b` refactor: Update asset feature tests to use Sanctum authentication and API routes.
- `cb248a2` refactor: Update feature tests to use API endpoints with Sanctum authentication and JSON assertions instead of Inertia page assertions.
- `fe3c78e` feat: Add a comprehensive report detailing the migration from Laravel+Inertia to a Laravel API + React Full SPA architecture.
- `5061def` refactor: Update E2E tests to use Bearer token authentication instead of XSRF-TOKEN and add an e2e test output file.
- `8f7842f` feat: Introduce API endpoints for approval audit trail and adjust frontend to use the new API route.
- `ab44cce` feat: Add API routes for pipeline audit trail and update frontend to fetch data from the new API endpoint.
- `4e42c73` feat: Implement client-side data fetching for the permissions index page via a new API endpoint and add new API routes for employee user management.
- `a5dbaf5` test: enhance admin settings E2E test by improving menu expansion logic and locator precision.
- `da88e5f` feat: Implement stock adjustment report export functionality and update frontend API endpoints to use the `/api` prefix.
- `53154a0` feat: Add inventory stocktake variance report export functionality and update API endpoints to use the `/api` prefix.
- `90cc906` feat: Add stock movement report export functionality and update API endpoints to use the `/api/` prefix.
- `8a1cd19` feat: Add inventory valuation export route and update report API endpoints to use the `/api/` prefix.
- `2f2dac4` Refactor: Update stock movements API endpoint to `/api/stock-movements` and enhance E2E test stability by using explicit element waits.
- `18fd4e1` fix: Await inventory stocktake detail API response before editing in E2E tests.
- `9c953d9` fix: Await inventory stocktake detail API response before editing in E2E tests.
- `d0039db` fix: Explicitly use `Storage::disk('public')` for file URLs in export actions and add API response waits to E2E stock transfer tests.
- `d4a61da` Refactor asset stocktake page to fetch stocktake data dynamically by ID, including loading and not found states.
- `b5555f6` feat: Implement API endpoint for asset profile with `react-query` frontend integration and standardize authorization error messages across employee management.
- `919daba` refactor: simplify e2e import tests by removing explicit API response waiting and parsing.
- `841f269` feat: Add export and import routes for numerous API resources.
- `bccc67a` feat: Improve data handling robustness in reports and dashboards, refactor report controller imports, and enhance E2E login test stability.
- `5ae1db9` chore: Remove build error log file.
- `fb65f28` refactor: Update pipeline dashboard to fetch data using React Query and add a script to migrate Inertia.js components to React Query, React Helmet, and React Router.
- `76bbbc1` refactor: Migrate Inertia.js `Head` and `Link` components to `react-helmet-async` and `react-router-dom` using a new refactoring script, and update Admin Settings to use `useSearchParams`.
- `5accda5` feat: Introduce dedicated API endpoints for admin settings and refactor the frontend to consume them, including a script to assist in Inertia component migration.
- `d304497` refactor: Migrate report pages to client-side data fetching using React Query and new JSON API endpoints, supported by a script to refactor Inertia components.
- `580ff57` refactor: Add script to migrate Inertia components to React Router and Helmet, and update the asset stocktake variance menu URL.
- `c914639` feat: Add script to automate refactoring of Inertia Head and Link components to React Helmet and Router, and update API pagination limits.
- `fae4629` refactor: Migrate stock monitor page to client-side filter fetching and add a script to refactor Inertia components for `react-helmet-async` and `react-router-dom`.
- `4a76495` refactor: update axios import paths to use internal library and introduce a script for Inertia.js component migration.
- `f3f0315` refactor: Remove `/v1` API route prefix and migrate frontend components from Inertia.js to `react-router-dom` and `react-helmet-async`.
- `55e8e13` feat: Implement lazy loading for all application routes and add a script to assist in migrating Inertia.js components to React Router DOM and React Helmet Async.
- `804ec2c` refactor: Migrate routing from Inertia.js to React Router DOM, implementing protected and guest routes.
- `cea9b52` feat: Add script to refactor Inertia.js components to React Helmet and React Router, and update login page to handle a simplified API response structure.
- `9c0a072` refactor: remove Inertia.js by migrating to a pure JSON API with client-side data fetching and an accompanying refactoring script.
- `da18ce8` refactor: Migrate the frontend from Inertia.js to a pure React SPA with API authentication and React Router.
