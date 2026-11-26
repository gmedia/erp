import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, currentPage, launchApp, loginInWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

test('Departments memuat tabel dengan data', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  const session = await launchApp(device);
  try {
    await loginInWebView(session);

    await expect
      .poll(async () => (await currentPage(session)).url(), { timeout: 90000 })
      .toMatch(/\/dashboard/);
    await (await currentPage(session)).waitForLoadState('networkidle');

    // Router aplikasi memakai BrowserRouter: pushState + popstate memicu navigasi React Router
    // tanpa reload WebView (reload memutus page dan memicu error "context has been closed").
    await expect
      .poll(
        async () => {
          const navPage = await currentPage(session);
          const [response] = await Promise.all([
            navPage
              .waitForResponse(
                (r) =>
                  new URL(r.url()).pathname === '/api/departments' &&
                  r.request().method() === 'GET' &&
                  r.status() === 200,
                { timeout: 20000 },
              )
              .catch(() => null),
            navPage
              .evaluate(() => {
                window.history.pushState({}, '', '/departments');
                window.dispatchEvent(new PopStateEvent('popstate'));
              })
              .catch(() => null),
          ]);
          return response !== null;
        },
        { timeout: 90000 },
      )
      .toBe(true);

    const rows = (await currentPage(session)).locator('table tbody tr');
    await expect(rows.first()).toBeVisible({ timeout: 60000 });
    expect(await rows.count()).toBeGreaterThan(0);
  } finally {
    await device.shell(`am force-stop ${APP_PKG}`);
  }
});
