import { expect, test, type Page } from '@playwright/test';
import { login } from '../helpers';

const DOWNLOAD_PAGE_URL = 'https://example.com/mobile-app';

interface MobileReleaseFixture {
    version_name: string;
    version_code: number;
    min_supported_version_code: number;
    download_page_url: string;
}

interface NativeTestWindow extends Window {
    __ERP_MOBILE_UPDATE_E2E__?: { forceNative: boolean; versionCode: number };
    __OPENED_URLS__?: Array<{ url: string; target: string }>;
}

async function prepareNativeApp(
    page: Page,
    versionCode: number,
): Promise<void> {
    await page.addInitScript(
        ({ installedVersionCode }) => {
            const testWindow = window as NativeTestWindow;

            testWindow.__ERP_MOBILE_UPDATE_E2E__ = {
                forceNative: true,
                versionCode: installedVersionCode,
            };
            testWindow.__OPENED_URLS__ = [];

            localStorage.setItem('app_locale', 'id');
            localStorage.setItem('appearance', 'light');

            window.open = ((url?: string | URL, target?: string) => {
                testWindow.__OPENED_URLS__?.push({
                    url: String(url ?? ''),
                    target: target ?? '',
                });

                return null;
            }) as typeof window.open;
        },
        { installedVersionCode: versionCode },
    );
}

async function mockLatestRelease(
    page: Page,
    release: MobileReleaseFixture,
): Promise<{ calls: () => number }> {
    let requestCount = 0;

    await page.route('**/api/mobile-app/latest', async (route) => {
        requestCount += 1;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ data: release }),
        });
    });

    return { calls: () => requestCount };
}

test.describe('Mobile update prompt', () => {
    test.use({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });

    test('web build does not request the latest release', async ({ page }) => {
        const latest = await mockLatestRelease(page, {
            version_name: '2.0.0',
            version_code: 200,
            min_supported_version_code: 1,
            download_page_url: DOWNLOAD_PAGE_URL,
        });

        await page.addInitScript(() => {
            localStorage.setItem('app_locale', 'id');
            localStorage.setItem('appearance', 'light');
        });

        await login(page);
        await page.goto('/dashboard');
        await page.waitForTimeout(1000);

        expect(latest.calls()).toBe(0);
        await expect(page.getByTestId('mobile-update-banner')).toHaveCount(0);
        await expect(
            page.getByTestId('mobile-update-mandatory-dialog'),
        ).toHaveCount(0);
    });

    test('native app stays quiet when the installed version is current', async ({
        page,
    }) => {
        await prepareNativeApp(page, 100);
        const latest = await mockLatestRelease(page, {
            version_name: '2.0.0',
            version_code: 100,
            min_supported_version_code: 1,
            download_page_url: DOWNLOAD_PAGE_URL,
        });

        await login(page);

        await expect.poll(latest.calls).toBe(1);
        await expect(page.getByTestId('mobile-update-banner')).toHaveCount(0);
        await expect(
            page.getByTestId('mobile-update-mandatory-dialog'),
        ).toHaveCount(0);
    });

    test('native app shows a dismissible banner for an optional update', async ({
        page,
    }) => {
        await prepareNativeApp(page, 100);
        await mockLatestRelease(page, {
            version_name: '2.0.0',
            version_code: 101,
            min_supported_version_code: 1,
            download_page_url: DOWNLOAD_PAGE_URL,
        });

        await login(page);

        const banner = page.getByTestId('mobile-update-banner');
        await expect(banner).toBeVisible();
        await expect(
            banner.getByText('Versi baru 2.0.0 tersedia'),
        ).toBeVisible();

        await page.getByTestId('mobile-update-banner-action').click();
        await expect
            .poll(() =>
                page.evaluate(
                    () => (window as NativeTestWindow).__OPENED_URLS__ ?? [],
                ),
            )
            .toEqual([{ url: DOWNLOAD_PAGE_URL, target: '_system' }]);

        await page.getByTestId('mobile-update-banner-dismiss').click();
        await expect(banner).toHaveCount(0);
        await expect(
            page.getByTestId('mobile-update-mandatory-dialog'),
        ).toHaveCount(0);
    });

    test('native app blocks the user with a mandatory update dialog', async ({
        page,
    }) => {
        await prepareNativeApp(page, 100);
        await mockLatestRelease(page, {
            version_name: '3.0.0',
            version_code: 300,
            min_supported_version_code: 200,
            download_page_url: DOWNLOAD_PAGE_URL,
        });

        await login(page);

        const dialog = page.getByTestId('mobile-update-mandatory-dialog');
        await expect(dialog).toBeVisible();
        await expect(
            dialog.getByText('Versi aplikasi ini tidak lagi didukung.'),
        ).toBeVisible();
        await expect(page.getByTestId('mobile-update-banner')).toHaveCount(0);

        await page.keyboard.press('Escape');
        await expect(dialog).toBeVisible();

        await page.mouse.click(4, 4);
        await expect(dialog).toBeVisible();

        await page.getByTestId('mobile-update-mandatory-action').click();
        await expect
            .poll(() =>
                page.evaluate(
                    () => (window as NativeTestWindow).__OPENED_URLS__ ?? [],
                ),
            )
            .toEqual([{ url: DOWNLOAD_PAGE_URL, target: '_system' }]);
        await expect(dialog).toBeVisible();
    });
});
