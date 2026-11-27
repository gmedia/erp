import { expect, test, type Page } from '@playwright/test';
import { login } from '../helpers';

test.describe('Issue #557: tabel mobile 390px locale id', () => {
    test.use({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });

    test.beforeEach(async ({ page }) => {
        await page.addInitScript(() =>
            localStorage.setItem('app_locale', 'id'),
        );
        await login(page);
    });

    async function openTable(page: Page, route: string) {
        await page.goto(route);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('tbody tr').first()).toBeVisible();
    }

    test('sel teks panjang memakai elipsis dan title berisi teks lengkap', async ({
        page,
    }) => {
        await openTable(page, '/products');

        const longCell = page
            .locator('tbody td .truncate')
            .filter({ hasText: /\S{3}/ })
            .first();
        await expect(longCell).toBeVisible();

        const metrics = await longCell.evaluate((el) => ({
            textOverflow: getComputedStyle(el).textOverflow,
            text: (el.textContent ?? '').trim(),
            title: el.getAttribute('title'),
        }));

        expect(metrics.textOverflow).toBe('ellipsis');
        expect(metrics.title).toBe(metrics.text);
    });

    test('tombol aksi baris pertama terlihat tanpa gulir di /products', async ({
        page,
    }) => {
        await openTable(page, '/products');
        await assertFirstRowActionsVisible(page);
    });

    test('tombol aksi baris pertama terlihat tanpa gulir di /purchase-orders', async ({
        page,
    }) => {
        await openTable(page, '/purchase-orders');
        await assertFirstRowActionsVisible(page);
    });
});

async function assertFirstRowActionsVisible(page: Page) {
    const actionCell = page
        .locator('tbody td[data-sticky-column="actions"]')
        .first();
    await expect(actionCell).toBeVisible();

    const { right, buttonRights, viewport } = await actionCell.evaluate(
        (cell) => {
            const rect = cell.getBoundingClientRect();
            const buttons = Array.from(cell.querySelectorAll('button, a'));
            return {
                right: rect.right,
                buttonRights: buttons.map(
                    (b) => b.getBoundingClientRect().right,
                ),
                viewport: document.documentElement.clientWidth,
            };
        },
    );

    expect(right).toBeLessThanOrEqual(viewport);
    expect(buttonRights.length).toBeGreaterThan(0);
    for (const buttonRight of buttonRights) {
        expect(buttonRight).toBeLessThanOrEqual(viewport);
    }
}
