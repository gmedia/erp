import { expect, test, type Page } from '@playwright/test';
import { openModule } from '../helpers';

async function expectReportNumericHeaderRightAligned(
    page: Page,
    label: string,
): Promise<void> {
    const head = page.locator('th', { hasText: label }).first();
    await expect(head).toBeVisible();
    const index = await head.evaluate(
        (el) =>
            Array.prototype.indexOf.call(el.parentElement!.children, el) + 1,
    );
    const valueText = page
        .locator(`tbody tr:first-child td:nth-child(${index}) > div`)
        .first();
    await expect(valueText).toBeVisible();

    const headTarget =
        (await head.locator('button').count()) > 0
            ? head.locator('button').first()
            : head.locator('div, span').first();
    const textRight = (el: Element) => {
        const range = document.createRange();
        range.selectNodeContents(el);
        return range.getBoundingClientRect().right;
    };
    const headRight = await headTarget.evaluate(textRight);
    const valueRight = await valueText.evaluate(textRight);
    expect(
        Math.abs(headRight - valueRight),
        `${label}: head right=${headRight} vs value text right=${valueRight}`,
    ).toBeLessThanOrEqual(4);
}

test.describe('Report Purchase History - lebar 1440', () => {
    test('tabel digeser horizontal di 1440 dengan petunjuk geser, Total Value dan total sejajar di ujung', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(
            page,
            '/reports/purchase-history',
            '/api/reports/purchase-history',
        );

        const totalHead = page
            .locator('th', { hasText: 'Total Value' })
            .first();
        await expect(totalHead).toBeVisible();

        const scroller = totalHead.locator(
            'xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " overflow-x-auto ")][1]',
        );
        await expect(scroller).toHaveCount(1);

        const overflows = await scroller.evaluate(
            (el) => el.scrollWidth > el.clientWidth,
        );
        expect(overflows).toBe(true);
        const tableContainer = page
            .locator('[data-slot="table-container"]')
            .first();
        await expect(tableContainer).toHaveAttribute(
            'data-overflow-right',
            'true',
        );

        await scroller.evaluate((el) => {
            el.scrollLeft = el.scrollWidth;
        });
        await expect
            .poll(() =>
                scroller.evaluate(
                    (el) =>
                        Math.abs(
                            el.scrollWidth - el.clientWidth - el.scrollLeft,
                        ) <= 1,
                ),
            )
            .toBe(true);

        const totalCell = page.getByTestId(
            'report-total-cell-total_purchase_value',
        );
        await expect(totalCell).toBeVisible();

        const rects = await scroller.evaluate((container, cellSelector) => {
            const c = container.getBoundingClientRect();
            const th = Array.from(container.querySelectorAll('th')).find((h) =>
                (h.textContent ?? '').includes('Total Value'),
            );
            const td = document.querySelector(cellSelector);
            return {
                containerLeft: c.left,
                containerRight: c.right,
                headRight: th ? th.getBoundingClientRect().right : null,
                headLeft: th ? th.getBoundingClientRect().left : null,
                cellRight: td ? td.getBoundingClientRect().right : null,
                cellLeft: td ? td.getBoundingClientRect().left : null,
            };
        }, '[data-testid="report-total-cell-total_purchase_value"]');

        expect(rects.headLeft).not.toBeNull();
        expect(rects.headLeft!).toBeGreaterThanOrEqual(rects.containerLeft - 1);
        expect(rects.headRight!).toBeLessThanOrEqual(rects.containerRight + 1);
        expect(rects.cellLeft!).toBeGreaterThanOrEqual(rects.containerLeft - 1);
        expect(rects.cellRight!).toBeLessThanOrEqual(rects.containerRight + 1);
        expect(
            Math.abs(rects.headRight! - rects.cellRight!),
            `Total Value head right=${rects.headRight} vs total cell right=${rects.cellRight}`,
        ).toBeLessThanOrEqual(4);
    });
});

test.describe('Report AP Aging - header angka', () => {
    test('header Amount Due rata kanan sejajar teks nilai di 1440', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/reports/ap-aging', '/api/reports/ap-aging');

        await expectReportNumericHeaderRightAligned(page, 'Amount Due');
    });

    test('header kolom aging non-sortable Current rata kanan', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/reports/ap-aging', '/api/reports/ap-aging');

        const head = page.locator('th', { hasText: 'Current' }).first();
        await expect(head).toBeVisible();
        const alignment = await head.evaluate(
            (el) => getComputedStyle(el.querySelector('div') ?? el).textAlign,
        );
        expect(['right', 'end']).toContain(alignment);
    });
});
