import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, loginInWebView, openAppWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

test('Departments memuat tabel dengan data', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  try {
    const page = await openAppWebView(device);
    await loginInWebView(page);

    const masterData = page.getByRole('button', { name: 'Master Data' }).first();
    await masterData.waitFor({ timeout: 90000 });
    await masterData.click();
    const menu = page.locator('a[href="/departments"]').first();
    await menu.waitFor({ timeout: 60000 });
    const departmentsResponse = page.waitForResponse(
      (r) => r.url().includes('/api/departments') && r.request().method() === 'GET',
      { timeout: 90000 },
    );
    await menu.click();
    expect((await departmentsResponse).ok()).toBe(true);

    const rows = page.locator('table tbody tr');
    await expect(rows.first()).toBeVisible({ timeout: 60000 });
    expect(await rows.count()).toBeGreaterThan(0);
  } finally {
    await device.shell(`am force-stop ${APP_PKG}`);
  }
});
