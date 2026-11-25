import { expect, test } from '@playwright/test';
import { login } from '../helpers';

// Issue #504 butir 5: halaman panjang (Stock Monitor) membuat sidebar
// ter-scroll otomatis ke item aktif. Item boleh terpotong oleh tepi kontainer
// scroll (sah), tetapi TIDAK boleh tampil di bawah/tertembus header logo.
test.describe('Sidebar top item visibility (#504)', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('item sidebar tidak tertembus header logo setelah auto-scroll', async ({
        page,
    }) => {
        await page.goto('/stock-monitor');
        await page.waitForURL('**/stock-monitor', { timeout: 15000 });

        const header = page.locator('[data-slot="sidebar-header"]').first();
        const content = page.locator('[data-slot="sidebar-content"]').first();
        const active = content.locator('[data-active="true"]').first();
        await expect(active).toBeVisible();

        const headerBox = await header.boundingBox();
        expect(headerBox).not.toBeNull();
        const headerBottom = headerBox!.y + headerBox!.height;

        const scrollTop = await content.evaluate((el) => el.scrollTop);
        expect(scrollTop).toBeGreaterThanOrEqual(0);

        const activeBox = await active.boundingBox();
        expect(activeBox).not.toBeNull();
        expect(activeBox!.y).toBeGreaterThanOrEqual(headerBottom - 1);

        // Aturan: item yang masih terlihat di kontainer tidak boleh mulai di
        // atas tepi bawah header. Pemotongan oleh tepi kontainer dibolehkan.
        const contentBox = await content.boundingBox();
        expect(contentBox).not.toBeNull();
        const items = content.locator('[data-sidebar="menu-button"]');
        const count = await items.count();
        for (let i = 0; i < count; i++) {
            const box = await items.nth(i).boundingBox();
            if (!box) continue;
            const visible =
                box.y + box.height > contentBox!.y &&
                box.y < contentBox!.y + contentBox!.height;
            if (!visible) continue;
            expect(
                box.y,
                `item ${i} tampil di bawah header logo`,
            ).toBeGreaterThanOrEqual(headerBottom - 1);
        }
    });
});
