import { expect, test } from '@playwright/test';
import { login } from '../helpers';

// Issue #504 butir 3: kolom Description Journal Entries di viewport mobile 390
// harus berelipsis dan terbaca utuh, tidak tertutup kolom aksi sticky.
test.describe('Journal Entries mobile Description column (#504)', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('description cell is ellipsized and not covered by actions', async ({
        page,
    }) => {
        const listResponse = page.waitForResponse(
            (r) =>
                r.request().method() === 'GET' &&
                r.url().includes('/api/journal-entries') &&
                !r.url().includes('/export'),
        );
        await page.goto('/journal-entries');
        await page.waitForURL('**/journal-entries', { timeout: 15000 });
        await listResponse;

        const headers = page.locator('thead th');
        await expect(headers.first()).toBeVisible();
        const descriptionIndex = await headers.evaluateAll((ths) =>
            ths.findIndex((th) =>
                (th.textContent ?? '').trim().startsWith('Description'),
            ),
        );
        expect(descriptionIndex).toBeGreaterThanOrEqual(0);

        const cell = page
            .locator('tbody tr')
            .first()
            .locator('td')
            .nth(descriptionIndex)
            .locator('div.truncate')
            .first();
        await expect(cell).toBeVisible();

        const style = await cell.evaluate((el) => {
            const s = getComputedStyle(el);
            return { overflow: s.overflow, textOverflow: s.textOverflow };
        });
        expect(style.overflow).toBe('hidden');
        expect(style.textOverflow).toBe('ellipsis');

        const actionsCell = page
            .locator('tbody tr')
            .first()
            .locator('td[data-sticky-column="actions"]');
        await expect(actionsCell).toHaveCount(1);

        // Kolom aksi sticky menutupi sel pada posisi geser awal; geser dulu.
        await cell.evaluate((el) => {
            let node: HTMLElement | null = el.parentElement;
            while (node && node.scrollWidth <= node.clientWidth) {
                node = node.parentElement;
            }
            if (node) {
                const delta =
                    el.getBoundingClientRect().left -
                    node.getBoundingClientRect().left;
                node.scrollLeft += delta;
            }
        });

        const box = await cell.boundingBox();
        const actionsBox = await actionsCell.boundingBox();
        expect(box).not.toBeNull();
        expect(actionsBox).not.toBeNull();
        if (box && actionsBox) {
            expect(box.x + box.width).toBeLessThanOrEqual(actionsBox.x + 1);
        }

        const containerRight = await page
            .locator('[data-slot="table-container"]')
            .first()
            .evaluate((el) => el.getBoundingClientRect().right);
        expect(actionsBox).not.toBeNull();
        if (actionsBox) {
            expect(
                Math.abs(actionsBox.x + actionsBox.width - containerRight),
            ).toBeLessThanOrEqual(1);
        }
    });
});
