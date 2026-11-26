import { appendFileSync, writeFileSync } from 'node:fs';
import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, currentPage, launchApp, loginInWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

const EVENTS_FILE = '/tmp/opencode/547-events.txt';
const NET_FILE = '/tmp/opencode/547-net.txt';
const logEvent = (msg: string) => {
  const line = `${new Date().toISOString()} ${msg}`;
  console.log(line);
  appendFileSync(EVENTS_FILE, `${line}\n`);
};

// Diagnosa jaringan: hanya metadata (method, path, status, ada/tidak header Authorization, scheme+host Origin).
// Nilai token dan header lain TIDAK dicatat.
const attachNetDiagnostics = (p: import('@playwright/test').Page, tag: string) => {
  p.on('response', (r) => {
    const url = new URL(r.url());
    if (!url.pathname.includes('/api/')) return;
    const headers = r.request().headers();
    const origin = headers['origin'];
    let originLabel = 'none';
    if (origin) {
      try {
        const o = new URL(origin);
        originLabel = `${o.protocol}//${o.host}`;
      } catch {
        originLabel = 'unparseable';
      }
    }
    appendFileSync(
      NET_FILE,
      `${new Date().toISOString()} [${tag}] ${r.request().method()} ${url.pathname} status=${r.status()} ` +
        `authorization=${'authorization' in headers} origin=${originLabel}\n`,
    );
  });
  p.on('console', (m) => {
    if (m.type() !== 'error') return;
    appendFileSync(NET_FILE, `${new Date().toISOString()} [${tag}] console.error ${m.text().slice(0, 300)}\n`);
  });
};

test('Departments memuat tabel dengan data', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  await device.shell('logcat -c');
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
    attachNetDiagnostics(session.page, 'page-initial');
    session.webview.on('page', (p) => {
      logEvent(`webview page event url=${p.url()}`);
      instrument(p, 'page-new');
      attachNetDiagnostics(p, 'page-new');
    });

    await loginInWebView(session);
    await new Promise((resolve) => setTimeout(resolve, 10000));
    logEvent(`post-login webViews=${device.webViews().length}`);

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
    const full = (await device.shell('logcat -d -b all -v time')).toString();
    writeFileSync('/tmp/opencode/547-logcat-full.txt', full);
    const logcat = full;
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
