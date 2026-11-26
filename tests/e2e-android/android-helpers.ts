import { _android as android, expect, type Page } from '@playwright/test';

export const APP_PKG = process.env.ANDROID_APP_ID ?? 'com.example.erp';
export const ADMIN_EMAIL = process.env.APP_ADMIN ?? 'admin@example.com';
export const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

type Device = Awaited<ReturnType<typeof android.devices>>[number];

export async function openAppWebView(device: Device): Promise<Page> {
  await device.shell(`am force-stop ${APP_PKG}`);
  await device.shell(`pm clear ${APP_PKG}`);
  await device.shell(`am start -n ${APP_PKG}/.MainActivity`);
  const webview = await device.webView({ pkg: APP_PKG });
  return webview.page();
}

export async function loginInWebView(page: Page): Promise<void> {
  const emailInput = page.locator('input[name="email"]');
  await expect
    .poll(async () => (await emailInput.count()) > 0 || /\/dashboard/.test(page.url()), { timeout: 90000 })
    .toBe(true);
  if (/\/dashboard/.test(page.url())) {
    return;
  }
  await page.locator('input[name="email"]').fill(ADMIN_EMAIL);
  await page.locator('input[name="password"]').fill(ADMIN_PASSWORD);

  const loginResponse = page.waitForResponse(
    (r) => r.url().includes('/api/login') && r.request().method() === 'POST',
  );
  await page.locator('[data-test="login-button"]').click();
  expect((await loginResponse).ok()).toBe(true);
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 90000 });
}
