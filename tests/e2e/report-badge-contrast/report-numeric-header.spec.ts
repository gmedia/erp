import { expect, test, type Page } from '@playwright/test';
import { openModule } from '../helpers';

// Standar repo (#463, budgets/table-numeric-layout.spec.ts) + keputusan
// reviewer #521 (butir 2): yang diukur adalah TEKS, bukan tombol.
// - ujung kanan TEKS label header (span label, bukan button) vs ujung kanan
//   TEKS nilai baris pertama, toleransi <= 2px.
// Pengukuran tombol tidak dipakai karena ikon sort di kiri membuat selisih
// teks ±13px lolos.
async function expectReportNumericHeaderRightAligned(
    page: Page,
    label: string,
): Promise<void> {
    const head = page.locator('th', { hasText: label }).first();
    const heads = await page.locator('thead th').allTextContents();
    expect(
        await head.count(),
        `header "${label}" tidak ada; th tersedia: ${heads.join(' | ')}`,
    ).toBeGreaterThan(0);
    await expect(head).toBeVisible();
    const index = await head.evaluate(
        (el) =>
            Array.prototype.indexOf.call(el.parentElement!.children, el) + 1,
    );
    const valueText = page
        .locator(`tbody tr:first-child td:nth-child(${index})`)
        .first();
    await expect(valueText).toBeVisible();

    const sortLabel = head.locator('span.line-clamp-2').first();
    const labelText =
        (await sortLabel.count()) > 0 ? sortLabel : head.locator('div').first();
    await expect(labelText).toBeVisible();

    const textRight = (el: Element) => {
        const range = document.createRange();
        range.selectNodeContents(el);
        return range.getBoundingClientRect().right;
    };
    const headRight = await labelText.evaluate(textRight);
    const valueRight = await valueText.evaluate(textRight);
    expect(
        Math.abs(headRight - valueRight),
        `${label}: head text right=${headRight} vs value text right=${valueRight}`,
    ).toBeLessThanOrEqual(2);
}

async function expectTotalAlignedWithFirstRow(
    page: Page,
    scrollerSelector: string,
): Promise<void> {
    const pairs = await page
        .locator(`${scrollerSelector} table`)
        .first()
        .evaluate((table) => {
            const first = table.tBodies[0]?.rows[0];
            const foot = table.tFoot?.rows[0];
            if (!first || !foot) {
                return [];
            }
            const textRight = (el: Element | null) => {
                if (!el) {
                    return null;
                }
                const range = document.createRange();
                range.selectNodeContents(el);
                return range.getBoundingClientRect().right;
            };
            const numeric: { col: number; data: number; total: number }[] = [];
            for (let i = 0; i < foot.cells.length; i++) {
                const totalCell = foot.cells[i];
                const dataCell = first.cells[i];
                if (
                    !totalCell ||
                    !dataCell ||
                    totalCell.textContent?.trim() === ''
                ) {
                    continue;
                }
                const totalText = totalCell.querySelector('*') ?? totalCell;
                const dataText = dataCell.querySelector('div') ?? dataCell;
                const total = textRight(totalText);
                const data = textRight(dataText);
                if (total !== null && data !== null && totalCell.className.includes('text-right')) {
                    numeric.push({ col: i, data, total });
                }
            }
            return numeric;
        });

    expect(pairs.length).toBeGreaterThan(0);
    for (const pair of pairs) {
        expect(
            Math.abs(pair.total - pair.data),
            `kolom ${pair.col}: total right=${pair.total} vs nilai baris pertama right=${pair.data}`,
        ).toBeLessThanOrEqual(2);
    }
}

async function expectRightEdgeShadowVisibleWhenOverflowing(
    page: Page,
): Promise<void> {
    const container = page.locator('[data-slot="table-container"]').first();
    await expect(container).toHaveAttribute('data-overflow-right', 'true');
    const shadow = await container.evaluate((el) => {
        const style = getComputedStyle(el, '::after');
        return {
            content: style.content,
            width: parseFloat(style.width),
            image: style.backgroundImage,
        };
    });
    expect(shadow.image).not.toBe('none');
    expect(shadow.width).toBeGreaterThanOrEqual(8);
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

    test('baris total sejajar kolom angka (<= 2px) dan bayangan kanan aktif saat overflow', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/reports/ap-aging', '/api/reports/ap-aging');

        await expectRightEdgeShadowVisibleWhenOverflowing(page);
        await expectTotalAlignedWithFirstRow(
            page,
            '[data-slot="table-container"]',
        );
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

test.describe('Badge status - tone dibedakan', () => {
    test('Cancelled berbeda dari Draft; Partially Received berbeda dari Confirmed', async () => {
        const { statusBadgeVariant } = await import(
            '../../../resources/js/lib/status-badge'
        );

        expect(statusBadgeVariant('cancelled')).not.toBe(
            statusBadgeVariant('draft'),
        );
        expect(statusBadgeVariant('partially_received')).not.toBe(
            statusBadgeVariant('confirmed'),
        );
        expect(statusBadgeVariant('partially_received')).not.toBe(
            statusBadgeVariant('pending_approval'),
        );
        expect(statusBadgeVariant('partially_received')).toBe('outline');
        expect(statusBadgeVariant('pending_approval')).toBe('warning');
        expect(statusBadgeVariant('cancelled')).toBe('destructive-soft');
    });
});

test.describe('Header angka rata kanan - modul bersama 1440', () => {
    test('Purchase Orders: header Grand Total sejajar teks nilai (<= 2px)', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/purchase-orders', '/api/purchase-orders');

        await expectReportNumericHeaderRightAligned(page, 'Grand Total');
    });

    test('AR Aging: header Amount Due (ReportColumns align kanan) sejajar teks nilai (<= 2px)', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/reports/ar-aging', '/api/reports/ar-aging');

        await expectReportNumericHeaderRightAligned(page, 'Amount Due');
    });
});

test.describe('Stock Monitor - header angka dan geser 1440', () => {
    test('header Qty On Hand, Avg Cost, Stock Value sejajar nilai (<= 2px) dan tabel muat tanpa geser', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/stock-monitor', '/api/stock-monitor');

        for (const label of ['Qty On Hand', 'Avg Cost', 'Stock Value']) {
            await expectReportNumericHeaderRightAligned(page, label);
        }
        const fitsWithoutScroll = await page
            .locator('[data-slot="table-container"]')
            .first()
            .locator('xpath=./div')
            .evaluate((el) => el.scrollWidth <= el.clientWidth + 1);
        expect(fitsWithoutScroll).toBe(true);
    });
});
