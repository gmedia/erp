import { writeFileSync } from 'node:fs';
import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, loginInWebView, openAppWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

test('Departments memuat tabel dengan data', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  try {
    const page = await openAppWebView(device);
    await loginInWebView(page);

    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: '/tmp/opencode/547-webview-before-nav.png', fullPage: true });
    const departmentsResponse = page.waitForResponse(
      (r) =>
        r.url().includes('/api/departments') &&
        r.request().method() === 'GET' &&
        r.status() === 200,
      { timeout: 90000 },
    );
    await page.evaluate(() => {
      window.history.pushState({}, '', '/departments');
      window.dispatchEvent(new PopStateEvent('popstate'));
    });
    expect((await departmentsResponse).ok()).toBe(true);

    const rows = page.locator('table tbody tr');
    await expect(rows.first()).toBeVisible({ timeout: 60000 });
    expect(await rows.count()).toBeGreaterThan(0);
  } finally {
    const logcat = (await device.shell('logcat -d')).toString();
    writeFileSync(
      '/tmp/opencode/547-logcat.txt',
      logcat
        .split('\n')
        .filter((line) => /chromium|Capacitor|AndroidRuntime/i.test(line))
        .join('\n'),
    );
    await device.shell(`am force-stop ${APP_PKG}`);
  }
});
