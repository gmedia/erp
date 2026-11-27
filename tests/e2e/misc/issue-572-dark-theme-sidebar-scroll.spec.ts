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
        await expect
            .poll(() => dialog.evaluate((el) => getComputedStyle(el).opacity))
            .toBe('1');

        const clipped = await dialog
            .locator('table th, table td, table td span')
            .evaluateAll((cells) =>
                cells
                    .map((c) => ({
                        text: c.textContent?.trim() ?? '',
                        overflow: c.scrollWidth > c.clientWidth + 1,
                        titled:
                            c.hasAttribute('title') ||
                            !!c.querySelector('[title]'),
                    }))
                    .filter((c) => c.overflow && !c.titled),
            );
        expect(clipped).toEqual([]);

        const bgAlpha = await dialog.evaluate((el) => {
            const css = getComputedStyle(el).backgroundColor;
            const ctx = document.createElement('canvas').getContext('2d');
            if (!ctx) return 0;
            ctx.clearRect(0, 0, 1, 1);
            ctx.fillStyle = css;
            ctx.fillRect(0, 0, 1, 1);
            return ctx.getImageData(0, 0, 1, 1).data[3] / 255;
        });
        expect(bgAlpha).toBe(1);

        await expect(
            dialog.getByText('Detail Konfigurasi Laporan'),
        ).toBeVisible();
        await expect(
            dialog.getByRole('columnheader', { name: 'Urutan' }),
        ).toBeVisible();
        await expect(
            dialog.getByRole('columnheader', { name: 'Order' }),
        ).toHaveCount(0);
        await expect(
            dialog.getByText('Report Configuration Details'),
        ).toHaveCount(0);
        await expect(
            dialog
                .locator('[data-slot="dialog-footer"]')
                .getByRole('button', { name: 'Tutup', exact: true }),
        ).toBeVisible();
        await expect(
            dialog
                .locator('[data-slot="dialog-footer"]')
                .getByRole('button', { name: 'Close', exact: true }),
        ).toHaveCount(0);
    });

    test('income statement modal at 390 dark: no hard-clipped text, Tutup footer, localized type badge', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.emulateMedia({ colorScheme: 'dark' });
        await login(page);
        await page.goto('/report-configurations', {
            waitUntil: 'domcontentloaded',
        });

        const row = page
            .locator('table tbody tr')
            .filter({ hasText: 'income_statement' })
            .first();
        await row.getByRole('button', { name: 'Aksi' }).click();
        await page
            .getByRole('menuitem')
            .filter({ hasText: /Lihat|View/ })
            .first()
            .click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('Laba Rugi')).toBeVisible();

        const hardClipped = await dialog
            .locator('table td, table th')
            .evaluateAll((cells) =>
                cells.flatMap((cell) => {
                    const container = cell.closest(
                        '[data-slot="dialog-content"]',
                    );
                    const limit = container
                        ? container.getBoundingClientRect().right + 1
                        : Number.POSITIVE_INFINITY;
                    return Array.from(cell.querySelectorAll('span, td'))
                        .filter((node) => {
                            const el = node as HTMLElement;
                            const text = el.textContent?.trim() ?? '';
                            if (!text) return false;
                            const range = document.createRange();
                            range.selectNodeContents(el);
                            const right = range.getBoundingClientRect().right;
                            if (right <= limit) return false;
                            const ellipsis =
                                getComputedStyle(el).textOverflow ===
                                'ellipsis';
                            return !ellipsis;
                        })
                        .map((node) => node.textContent?.trim() ?? '');
                }),
            );
        expect(hardClipped).toEqual([]);

        const tableScroll = await dialog
            .locator('table')
            .first()
            .evaluate((table) => {
                const scroller = table.parentElement;
                return {
                    scrollable:
                        !!scroller &&
                        scroller.scrollWidth > scroller.clientWidth,
                    hasEdgeCue: !!table.closest(
                        '[data-slot="table-container"]',
                    ),
                };
            });
        expect(tableScroll.hasEdgeCue).toBe(true);
        expect(tableScroll.scrollable).toBe(true);

        await expect(
            dialog
                .locator('table tbody tr td [data-slot="badge"]')
                .filter({ hasText: /^(Judul|Rincian|Subtotal|Total)$/ })
                .first(),
        ).toBeAttached();
        await expect(
            dialog
                .locator('table tbody tr td [data-slot="badge"]')
                .filter({ hasText: /^(Header|Detail|header|detail)$/ }),
        ).toHaveCount(0);
        await expect(
            dialog
                .locator('[data-slot="dialog-footer"]')
                .getByRole('button', { name: 'Tutup', exact: true }),
        ).toBeVisible();
        await expect(
            dialog.getByRole('button', { name: 'Close', exact: true }),
        ).toHaveCount(0);
    });
});
