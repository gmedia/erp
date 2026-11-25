import { expect, test } from '@playwright/test';
import { login } from '../helpers';

// Issue #504 butir 5: item sidebar teratas tidak boleh terpotong di bawah
// header logo, meski halaman panjang (Stock Monitor) membuat sidebar ter-scroll.
test.describe('Sidebar top item visibility (#504)', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('first sidebar menu item is fully below the logo header', async ({
        page,
    }) => {
        await page.goto('/stock-monitor');
        await page.waitForURL('**/stock-monitor', { timeout: 15000 });

        const header = page.locator('[data-slot="sidebar-header"]').first();
        const content = page.locator('[data-slot="sidebar-content"]').first();
        const firstItem = content
            .locator('[data-sidebar="menu-button"]')
            .first();
        await expect(firstItem).toBeVisible();

        const headerBox = await header.boundingBox();
        const contentBox = await content.boundingBox();
        expect(headerBox).not.toBeNull();
        expect(contentBox).not.toBeNull();
        if (headerBox && contentBox) {
            expect(contentBox.y).toBeGreaterThanOrEqual(
                headerBox.y + headerBox.height - 1,
            );
        }

        const scrollTop = await content.evaluate((el) => el.scrollTop);
        if (scrollTop === 0) {
            const itemBox = await firstItem.boundingBox();
            expect(itemBox).not.toBeNull();
            if (itemBox && contentBox) {
                expect(itemBox.y).toBeGreaterThanOrEqual(contentBox.y - 1);
            }
        }
    });
});
