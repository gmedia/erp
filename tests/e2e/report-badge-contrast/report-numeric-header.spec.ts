import { expect, test, type Page } from '@playwright/test';
import { openModule } from '../helpers';

async function expectReportNumericHeaderRightAligned(
    page: Page,
    label: string,
): Promise<void> {
    const head = page.locator('th', { hasText: label }).first();
    await expect(head).toBeVisible();
    const index = await head.evaluate(
        (el) => Array.prototype.indexOf.call(el.parentElement!.children, el) + 1,
    );
    const valueText = page
        .locator(`tbody tr:first-child td:nth-child(${index}) > div`)
        .first();
    await expect(valueText).toBeVisible();

    // Tepi kanan tombol header dibandingkan dengan teks nilai (pola #463).
    const headRight = await head
        .locator('button')
        .first()
        .evaluate((el) => el.getBoundingClientRect().right);
    const valueRight = await valueText.evaluate(
        (el) => el.getBoundingClientRect().right,
    );
    expect(
        Math.abs(headRight - valueRight),
        `${label}: head button right=${headRight} vs value text right=${valueRight}`,
    ).toBeLessThanOrEqual(4);
}

test.describe('Report AP Aging - header angka', () => {
    test('header Amount Due rata kanan sejajar teks nilai di 1440', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(
            page,
            '/reports/ap-aging',
            '/api/reports/ap-aging',
        );

        await expectReportNumericHeaderRightAligned(page, 'Amount Due');
    });

    test('header kolom aging non-sortable Current rata kanan', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(
            page,
            '/reports/ap-aging',
            '/api/reports/ap-aging',
        );

        const head = page.locator('th', { hasText: 'Current' }).first();
        await expect(head).toBeVisible();
        const alignment = await head.evaluate(
            (el) => getComputedStyle(el.querySelector('div') ?? el).textAlign,
        );
        expect(['right', 'end']).toContain(alignment);
    });
});
