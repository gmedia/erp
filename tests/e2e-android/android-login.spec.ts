import { _android as android, expect, test } from '@playwright/test';
import { APP_PKG, launchApp, loginInWebView } from './android-helpers';

test.describe.configure({ mode: 'serial' });

test('login lalu tampil dashboard', async () => {
  const [device] = await android.devices();
  expect(device, 'emulator/device tidak terdeteksi').toBeDefined();

  try {
    const session = await launchApp(device);
    await loginInWebView(session);
  } finally {
    await device.shell(`am force-stop ${APP_PKG}`);
  }
});
