import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('issue #572 appearance, sidebar scroll, report sections', () => {
    test.beforeEach(async ({ page }) => {
        await page.addInitScript(() => {
            localStorage.setItem('app_locale', 'id');
        });
    });

    test('appearance option labels keep readable contrast in dark theme', async ({
        page,
    }) => {
        await login(page);
        await page.emulateMedia({ colorScheme: 'dark' });
        await page.goto('/settings/appearance', {
            waitUntil: 'domcontentloaded',
        });

        const options = page
            .locator('button')
            .filter({ hasText: /^(Terang|Gelap|Sistem|Light|Dark|System)$/ });
        await expect(options).toHaveCount(3);

        for (let i = 0; i < 3; i++) {
            const ratio = await options.nth(i).evaluate((el) => {
                const parse = (css: string): number[] => {
                    const ctx = document
                        .createElement('canvas')
                        .getContext('2d');
                    if (!ctx) return [0, 0, 0];
                    ctx.fillStyle = css;
                    ctx.fillRect(0, 0, 1, 1);
                    const d = ctx.getImageData(0, 0, 1, 1).data;
                    return [d[0], d[1], d[2]];
                };
                const lum = (rgb: number[]): number => {
                    const c = rgb.map((v) => {
                        const s = v / 255;
                        return s <= 0.03928
                            ? s / 12.92
                            : ((s + 0.055) / 1.055) ** 2.4;
                    });
                    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
                };
                let bgEl: HTMLElement = el as HTMLElement;
                let bg = getComputedStyle(bgEl).backgroundColor;
                while (bg === 'rgba(0, 0, 0, 0)' && bgEl.parentElement) {
                    bgEl = bgEl.parentElement;
                    bg = getComputedStyle(bgEl).backgroundColor;
                }
                const fg = lum(parse(getComputedStyle(el).color));
                const back = lum(parse(bg));
                return (
                    (Math.max(fg, back) + 0.05) / (Math.min(fg, back) + 0.05)
                );
            });
            expect(ratio, `option ${i} dark contrast`).toBeGreaterThanOrEqual(
                4.5,
            );
        }
    });

    test('sidebar keeps its scroll position across navigation', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 600 });
        await login(page);
        await page.goto('/dashboard', { waitUntil: 'domcontentloaded' });

        const sidebar = page.locator('[data-sidebar="content"]').first();
        await expect(sidebar).toBeVisible();
        await sidebar.evaluate((el) => {
            el.scrollTop = el.scrollHeight;
        });
        const before = await sidebar.evaluate((el) => el.scrollTop);
        expect(before).toBeGreaterThan(0);

        const target = page
            .locator(
                '[data-sidebar="content"] a[href^="/"]:not([href="/dashboard"])',
            )
            .first();
        const href = await target.getAttribute('href');
        expect(href).toBeTruthy();
        await target.click();
        await page.waitForURL((url) => url.pathname === href);

        const sidebarAfter = page.locator('[data-sidebar="content"]').first();
        await expect
            .poll(() => sidebarAfter.evaluate((el) => el.scrollTop))
            .toBeGreaterThan(0);
        const after = await sidebarAfter.evaluate((el) => el.scrollTop);
        expect(after).toBeLessThanOrEqual(before);

        const activeVisible = await sidebarAfter.evaluate((el) => {
            const active = el.querySelector<HTMLElement>(
                '[data-active="true"]',
            );
            if (!active) return true;
            const a = active.getBoundingClientRect();
            const c = el.getBoundingClientRect();
            return a.top >= c.top && a.bottom <= c.bottom;
        });
        expect(activeVisible).toBe(true);
    });

    test('report configuration sections table fits 1440 without clipped cells', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await login(page);
        await page.goto('/report-configurations', {
            waitUntil: 'domcontentloaded',
        });

        await page
            .locator('table tbody tr')
            .first()
            .getByRole('button', { name: 'Aksi' })
            .click();
        await page
            .getByRole('menuitem')
            .filter({ hasText: /Lihat|View/ })
            .first()
            .click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        const clipped = await dialog
            .locator('table th, table td')
            .evaluateAll((cells) =>
                cells
                    .map((c) => ({
                        text: c.textContent?.trim() ?? '',
                        overflow: c.scrollWidth > c.clientWidth + 1,
                    }))
                    .filter((c) => c.overflow),
            );
        expect(clipped).toEqual([]);
    });
});
