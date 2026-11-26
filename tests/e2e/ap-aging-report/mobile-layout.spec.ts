import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { openApAgingReport } from './helpers';

// Issue #504 butir 2: AP Aging di viewport mobile 390.
test.describe('AP Aging Report mobile layout (#504)', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('supplier cell truncates with ellipsis instead of clipping', async ({
        page,
    }) => {
        await openApAgingReport(page);

        const supplierCell = page
            .locator('tbody tr')
            .first()
            .locator('td')
            .nth(2)
            .locator('div.truncate')
            .first();
        await expect(supplierCell).toBeVisible();

        const overflow = await supplierCell.evaluate((el) => {
            const style = getComputedStyle(el);
            return {
                overflow: style.overflow,
                textOverflow: style.textOverflow,
                whiteSpace: style.whiteSpace,
            };
        });
        expect(overflow.overflow).toBe('hidden');
        expect(overflow.textOverflow).toBe('ellipsis');
        expect(overflow.whiteSpace).toBe('nowrap');
    });

    test('summary total background spans the full table width', async ({
        page,
    }) => {
        await openApAgingReport(page);

        const widths = await page.evaluate(() => {
            const table = document.querySelector('table');
            const footer = document.querySelector(
                '[data-testid="report-summary"]',
            );
            const tr = footer?.querySelector('tr');
            return {
                table: table?.getBoundingClientRect().width ?? 0,
                footer: tr?.getBoundingClientRect().width ?? 0,
            };
        });
        expect(widths.footer).toBeGreaterThanOrEqual(widths.table - 1);
    });

    test('horizontal scroll hint is shown when table overflows', async ({
        page,
    }) => {
        await openApAgingReport(page);

        const container = page.locator('[data-slot="table-container"]').first();
        await expect(container).toHaveAttribute('data-overflow-right', 'true');

        const fade = await container.evaluate((el) => {
            const style = getComputedStyle(el, '::after');
            return {
                width: parseFloat(style.width),
                image: style.backgroundImage,
            };
        });
        expect(fade.width).toBeGreaterThanOrEqual(16);
        expect(fade.image).not.toBe('none');
    });

    test('last column aligns with container edge after scrolling to end', async ({
        page,
    }) => {
        await openApAgingReport(page);

        const container = page.locator('[data-slot="table-container"]').first();
        const scroller = container.locator('> div').first();
        await scroller.evaluate((el: HTMLElement) => {
            el.scrollLeft = el.scrollWidth;
        });
        await expect(container).not.toHaveAttribute('data-overflow-right', 'true');

        const edges = await page.evaluate(() => {
            const c = document.querySelector(
                '[data-slot="table-container"]',
            ) as HTMLElement;
            const lastTd = c.querySelector('tbody tr td:last-child');
            return {
                container: c.getBoundingClientRect().right,
                cell: lastTd?.getBoundingClientRect().right ?? 0,
            };
        });
        expect(Math.abs(edges.cell - edges.container)).toBeLessThanOrEqual(1);
    });
});
