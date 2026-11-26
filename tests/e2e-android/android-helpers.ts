import { _android as android, expect, type Page } from '@playwright/test';

export const APP_PKG = process.env.ANDROID_APP_ID ?? 'com.example.erp';
export const ADMIN_EMAIL = process.env.APP_ADMIN ?? 'admin@example.com';
export const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

type Device = Awaited<ReturnType<typeof android.devices>>[number];
type WebView = Awaited<ReturnType<Device['webView']>>;

export interface AppSession {
  device: Device;
  webview: WebView;
  page: Page;
}

/**
 * Membuka aplikasi dan mengambil WebView sekali saja (device.webView dipanggil satu kali).
 * Page yang dikembalikan dipakai sampai akhir spec; bila page berganti karena redirect,
 * gunakan `currentPage()` (memanggil webview.page() pada objek yang sama).
 */
export async function launchApp(device: Device): Promise<AppSession> {
  await device.shell(`am force-stop ${APP_PKG}`);
  await device.shell(`pm clear ${APP_PKG}`);
  await device.shell(`am start -n ${APP_PKG}/.MainActivity`);
  // Setelah pm clear, WebView baru bisa butuh >30 dtk untuk muncul di emulator; beri batas lebih longgar.
  const webview = await device.webView({ pkg: APP_PKG }, { timeout: 90000 });
  const page = await webview.page();
  return { device, webview, page };
}

// Activity bisa dibuat ulang oleh sistem (ThemeOverlayApplier mengubah asset paths di emulator);
// WebView lama lalu tertutup, sehingga WebView baru diambil sekali di sini.
export async function currentPage(session: AppSession): Promise<Page> {
  if (session.page.isClosed()) {
    await expect
      .poll(async () => {
        session.webview = await session.device.webView({ pkg: APP_PKG });
        session.page = await session.webview.page();
        return session.page.url().startsWith('https://localhost');
      }, { timeout: 60000 })
      .toBe(true);
  }
  return session.page;
}

export async function loginInWebView(session: AppSession): Promise<void> {
  await expect
    .poll(async () => (await (await currentPage(session)).locator('input[name="email"]').count()) > 0, {
      timeout: 90000,
    })
    .toBe(true);
  await (await currentPage(session)).locator('input[name="email"]').fill(ADMIN_EMAIL);
  await (await currentPage(session)).locator('input[name="password"]').fill(ADMIN_PASSWORD);

  const page = await currentPage(session);
  const loginResponse = page.waitForResponse(
    (r) => new URL(r.url()).pathname === '/api/login' && r.request().method() === 'POST',
    { timeout: 60000 },
  );
  await page.locator('[data-test="login-button"]').click();
  expect((await loginResponse).ok()).toBe(true);
  await expect.poll(async () => /\/dashboard/.test((await currentPage(session)).url()), { timeout: 90000 }).toBe(true);
}
