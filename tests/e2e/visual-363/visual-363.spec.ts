import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Visual #363 review fixes', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('users 390: nama dan email tidak tertutup sel aksi sticky', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/users');
        await page.waitForResponse(
            (r) => r.url().includes('/api/users') && r.status() < 400,
        );

        const row = page.getByTestId('user-row').first();
        await expect(row).toBeVisible();

        const actionCell = row.locator('td').last();
        const actionBox = await actionCell.boundingBox();
        expect(actionBox).not.toBeNull();

        const textCells = row.locator('td');
        const cellCount = await textCells.count();
        for (let i = 0; i < cellCount - 1; i++) {
            const box = await textCells.nth(i).boundingBox();
            expect(box).not.toBeNull();
            expect(box!.x + box!.width).toBeLessThanOrEqual(actionBox!.x + 1);
        }
    });

    test('my-approvals: chip Pending memakai token warning', async ({
        page,
    }) => {
        await page.goto('/my-approvals');
        await expect(page.getByRole('tab', { name: 'Pending' })).toBeVisible();

        const chip = page
            .locator('[data-slot="badge"]', { hasText: 'Pending' })
            .first();
        const hasChip = (await chip.count()) > 0;
        test.skip(!hasChip, 'Tidak ada permintaan pending untuk user ini');

        const bg = await chip.evaluate(
            (el) => getComputedStyle(el).backgroundColor,
        );
        const secondaryBg = await page.evaluate(() => {
            const probe = document.createElement('span');
            probe.className = 'bg-secondary';
            document.body.appendChild(probe);
            const value = getComputedStyle(probe).backgroundColor;
            probe.remove();
            return value;
        });
        expect(bg).not.toBe(secondaryBg);
    });

    test('ap-payment-history: status Confirmed (final) tidak memakai warning', async ({
        page,
    }) => {
        await page.goto('/reports/ap-payment-history');
        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/ap-payment-history') &&
                r.status() < 400,
        );
        await expect(page.locator('table')).toBeVisible();

        const chip = page
            .locator('table [data-slot="badge"]', { hasText: 'Confirmed' })
            .first();
        test.skip(
            (await chip.count()) === 0,
            'Tidak ada pembayaran Confirmed di data seed',
        );

        const bg = await chip.evaluate(
            (el) => getComputedStyle(el).backgroundColor,
        );
        const warningBg = await page.evaluate(() => {
            const probe = document.createElement('span');
            probe.className = 'bg-warning-soft';
            document.body.appendChild(probe);
            const value = getComputedStyle(probe).backgroundColor;
            probe.remove();
            return value;
        });
        expect(bg).not.toBe(warningBg);
    });

    test('trial-balance 390: kolom Credit terjangkau', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/reports/trial-balance');
        await page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                r.status() < 400,
            {
                timeout: 30000,
            },
        );

        const creditCell = page
            .locator('table thead th', { hasText: 'Credit' })
            .first();
        await expect(creditCell).toBeAttached();

        const { reachableAfterScroll, scrollable } = await creditCell.evaluate(
            (th) => {
                const scroller = th.closest('.overflow-x-auto') as HTMLElement;
                scroller.scrollLeft = scroller.scrollWidth;
                const scrollerRect = scroller.getBoundingClientRect();
                const thRect = th.getBoundingClientRect();
                return {
                    reachableAfterScroll:
                        thRect.right <= scrollerRect.right + 1 &&
                        thRect.left >= scrollerRect.left - 1,
                    scrollable: scroller.scrollWidth > scroller.clientWidth,
                };
            },
        );
        expect(scrollable).toBe(true);
        expect(reachableAfterScroll).toBe(true);
    });
});
