import { expect, test, type Page } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { login } from '../helpers';

const EVIDENCE_DIR = 'e2e/e2e-full/sidebar-top-fade';
const CONTENT = '[data-sidebar="content"]';

async function scrolledTop(page: Page): Promise<string | undefined> {
    return page
        .locator(CONTENT)
        .first()
        .evaluate((el) => (el as HTMLElement).dataset.scrolledTop);
}

async function scrollSidebarTo(page: Page, top: number): Promise<void> {
    await page
        .locator(CONTENT)
        .first()
        .evaluate((el, y) => {
            el.scrollTop = y;
        }, top);
}

for (const theme of ['light', 'dark'] as const) {
    test.describe(`sidebar top fade (${theme})`, () => {
        test.beforeEach(async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            await page.addInitScript(() =>
                localStorage.setItem('app_locale', 'id'),
            );
            await login(page, undefined, undefined, {
                requireDashboard: true,
            });
        });

        test('fade atas aktif hanya setelah sidebar tergulir', async ({
            page,
        }) => {
            await page.setViewportSize({ width: 1440, height: 900 });
            await page.goto('/products', { waitUntil: 'domcontentloaded' });
            await page.waitForLoadState('networkidle').catch(() => undefined);

            const content = page.locator(CONTENT).first();
            await expect(content).toBeVisible();

            await scrollSidebarTo(page, 0);
            await expect.poll(() => scrolledTop(page)).toBe('false');

            await scrollSidebarTo(page, 120);
            await expect.poll(() => scrolledTop(page)).toBe('true');

            mkdirSync(EVIDENCE_DIR, { recursive: true });
            await page.screenshot({
                path: `${EVIDENCE_DIR}/${theme}-scrolled.png`,
            });

            await scrollSidebarTo(page, 0);
            await expect.poll(() => scrolledTop(page)).toBe('false');
        });
    });
}
