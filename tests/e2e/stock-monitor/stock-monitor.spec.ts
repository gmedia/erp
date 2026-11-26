import { test, expect } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

test.describe('Stock Monitor Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view stock monitor summary and filter data', async ({ page }) => {
        const initialResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        await page.waitForURL('**/stock-monitor', { timeout: 15000 });
        await expectResponseOk(await initialResponsePromise);

        await expect(page.getByText('Total SKU-Warehouse')).toBeVisible();
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const exportResponsePromise = page.waitForResponse(
            (r) => r.url().includes('/api/stock-monitor/export'),
        );
        await page.getByRole('button', { name: /export/i }).click();
        await expectResponseOk(await exportResponsePromise);

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill('P-');
        const searchResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await searchInput.press('Enter');
        await expectResponseOk(await searchResponsePromise);

        await expect(page.locator('tbody tr')).toHaveCount(1, {
            timeout: 15000,
        });

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('dialog').getByPlaceholder('Example: 10').fill('1000');
        const filterResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/stock-monitor') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await expectResponseOk(await filterResponsePromise);

        await expect(page.getByText('Low Stock Items')).toBeVisible();

    });

    test('menampilkan kartu nilai stok dalam perjalanan dari ringkasan', async ({ page }) => {
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor') &&
                !r.url().includes('export') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        const response = await responsePromise;
        await expectResponseOk(response);

        const body = await response.json();
        expect(body.summary.in_transit).toEqual(
            expect.objectContaining({
                total_items: expect.any(Number),
                total_quantity: expect.any(String),
                total_value: expect.any(String),
            }),
        );

        await expect(page.getByText('In-Transit Stock Value')).toBeVisible();
    });

    test('header kolom angka sejajar kanan dengan nilainya di viewport 1440', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor') &&
                !r.url().includes('export') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        await expectResponseOk(await responsePromise);
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const aligned = await page.evaluate(() => {
            const headers = Array.from(document.querySelectorAll('thead th'));
            const firstRow = document.querySelector('tbody tr');
            const cells = Array.from(firstRow?.querySelectorAll('td') ?? []);
            const pairs = ['Qty On Hand', 'Avg Cost', 'Stock Value'].map((label) => {
                const index = headers.findIndex((th) => th.textContent?.includes(label));
                const th = headers[index];
                const td = cells[index];
                if (!th || !td) return null;
                const valueRight = td.getBoundingClientRect().right;
                const headerRight = th.getBoundingClientRect().right;
                return Math.abs(valueRight - headerRight);
            });
            return pairs;
        });
        for (const gap of aligned) {
            expect(gap).not.toBeNull();
            expect(gap!).toBeLessThanOrEqual(12);
        }
    });

    test('kolom Last Movement terlihat utuh di viewport 1440 tanpa keluar kontainer', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor') &&
                !r.url().includes('export') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        await expectResponseOk(await responsePromise);
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const edges = await page.evaluate(() => {
            const container = document.querySelector('[data-slot="table-container"]');
            const ths = Array.from(document.querySelectorAll('thead th'));
            const lastTh = ths.find((th) => th.textContent?.includes('Last Movement'));
            const index = ths.indexOf(lastTh as HTMLTableCellElement);
            const cell = document.querySelector('tbody tr')?.querySelectorAll('td')[index];
            return {
                containerRight: container?.getBoundingClientRect().right ?? null,
                cellRight: cell?.getBoundingClientRect().right ?? null,
            };
        });
        expect(edges.containerRight).not.toBeNull();
        expect(edges.cellRight).not.toBeNull();
        expect(edges.cellRight!).toBeLessThanOrEqual(edges.containerRight! + 1);
    });

    test('petunjuk geser tampil di 390 saat tabel melebihi viewport', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const responsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-monitor') &&
                !r.url().includes('export') &&
                r.request().headers()['accept']?.includes('application/json'),
        );
        await page.goto('/stock-monitor');
        await expectResponseOk(await responsePromise);
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const container = page.locator('[data-slot="table-container"]').first();
        await expect(container).toHaveAttribute('data-overflow-right', 'true');
    });
});
