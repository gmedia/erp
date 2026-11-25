import { expect, test } from '@playwright/test';
import { openModule } from '../helpers';

test.describe('DataTable layout di mobile (390px)', () => {
    test.use({ viewport: { width: 390, height: 844 } });

    test('sel sebelum kolom aksi tidak tertutup dan header angka rata kanan', async ({
        page,
    }) => {
        await openModule(page, '/employees', '/api/employees');

        const actionsHead = page.locator('th[data-sticky-column="actions"]');
        await expect(actionsHead).toBeVisible();
        await page.evaluate(() => document.fonts.ready);

        const lastBeforeActions = actionsHead.locator(
            'xpath=preceding-sibling::th[1]',
        );
        await expect
            .poll(
                async () => {
                    await actionsHead.evaluate((el) => {
                        const scroller = el.closest('.overflow-x-auto');
                        if (scroller) {
                            scroller.scrollLeft = scroller.scrollWidth;
                        }
                    });
                    const actionsBox = await actionsHead.boundingBox();
                    const beforeBox = await lastBeforeActions.boundingBox();
                    if (!actionsBox || !beforeBox)
                        return Number.POSITIVE_INFINITY;
                    return beforeBox.x + beforeBox.width - (actionsBox.x + 1);
                },
                { timeout: 10000 },
            )
            .toBeLessThanOrEqual(0);

        const salaryHead = page.locator('th', { hasText: 'Salary' }).first();
        await expect(salaryHead).toBeVisible();
        const headerAlign = await salaryHead.evaluate((el) => {
            const sortButton = el.querySelector('button');
            return sortButton
                ? getComputedStyle(sortButton.parentElement as Element)
                      .justifyContent
                : getComputedStyle(el).textAlign;
        });
        expect(['flex-end', 'end', 'right']).toContain(headerAlign);
    });
});
