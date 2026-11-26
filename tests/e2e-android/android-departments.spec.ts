import { appendFileSync, writeFileSync } from 'node:fs';
import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, currentPage, launchApp, loginInWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

const EVENTS_FILE = '/tmp/opencode/547-events.txt';
const logEvent = (msg: string) => {
  const line = `${new Date().toISOString()} ${msg}`;
  console.log(line);
  appendFileSync(EVENTS_FILE, `${line}\n`);
};

test('Departments memuat tabel dengan data', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  const session = await launchApp(device);
  try {
    const instrument = (p: import('@playwright/test').Page, tag: string) => {
      p.on('close', () => logEvent(`[${tag}] page close`));
      p.on('load', () => logEvent(`[${tag}] page load url=${p.url()}`));
      p.on('framenavigated', (f) => {
        if (f === p.mainFrame()) logEvent(`[${tag}] framenavigated url=${f.url()}`);
      });
    };
    instrument(session.page, 'page-initial');
    session.webview.on('page', (p) => {
      logEvent(`webview page event url=${p.url()}`);
      instrument(p, 'page-new');
    });

    await loginInWebView(session);
    await new Promise((resolve) => setTimeout(resolve, 10000));
    const probe = await currentPage(session);
    logEvent(`post-login isClosed=${probe.isClosed()} url=${probe.url()} webViews=${device.webViews().length}`);

    const page = await currentPage(session);
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 90000 });
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: '/tmp/opencode/547-webview-before-nav.png' });

    // Router aplikasi memakai BrowserRouter: pushState + popstate memicu navigasi React Router
    // tanpa reload WebView (reload memutus page dan memicu error "context has been closed").
    const activePage = await currentPage(session);
    const [departmentsResponse] = await Promise.all([
      activePage.waitForResponse(
        (r) =>
          new URL(r.url()).pathname === '/api/departments' &&
          r.request().method() === 'GET' &&
          r.status() === 200,
        { timeout: 60000 },
      ),
      activePage.evaluate(() => {
        window.history.pushState({}, '', '/departments');
        window.dispatchEvent(new PopStateEvent('popstate'));
      }),
    ]);
    expect(departmentsResponse.ok()).toBe(true);

    const rows = (await currentPage(session)).locator('table tbody tr');
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
