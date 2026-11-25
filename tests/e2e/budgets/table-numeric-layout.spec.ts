import { expect, test, type Locator, type Page } from '@playwright/test';
import { openModule } from '../helpers';

async function boxOf(locator: Locator) {
    const box = await locator.boundingBox();
    expect(box).not.toBeNull();
    return box!;
}

function columnIndexOf(head: Locator) {
    return head.evaluate(
        (el) =>
            Array.prototype.indexOf.call(el.parentElement!.children, el) + 1,
    );
}

// Tepi kanan tombol header dibandingkan dengan teks nilai, bukan tepi sel.
async function expectNumericHeaderRightAligned(
    page: Page,
    label: string,
): Promise<void> {
    const head = page.locator('th', { hasText: label }).first();
    await expect(head).toBeVisible();
    const index = await columnIndexOf(head);
    const valueText = page
        .locator(`tbody tr:first-child td:nth-child(${index}) > div`)
        .first();
    await expect(valueText).toBeVisible();

    const headRight = await head
        .locator('button')
        .first()
        .evaluate((el) => el.getBoundingClientRect().right);
    const valueRight = await valueText.evaluate(
        (el) => el.getBoundingClientRect().right,
    );
    expect(Math.abs(headRight - valueRight)).toBeLessThanOrEqual(4);
}

async function expectRoomBeforeActions(page: Page): Promise<void> {
    const actionsHead = page.locator('th[data-sticky-column="actions"]');
    await expect(actionsHead).toBeVisible();
    await actionsHead.evaluate((el) => {
        const scroller = el.closest('.overflow-x-auto');
        if (scroller) scroller.scrollLeft = scroller.scrollWidth;
    });
    const beforeHead = actionsHead.locator('xpath=preceding-sibling::th[1]');
    const actionsBox = await boxOf(actionsHead);
    const beforeBox = await boxOf(beforeHead);
    expect(beforeBox.x + beforeBox.width).toBeLessThanOrEqual(actionsBox.x + 1);
}

test.describe('Budgets - kolom angka & layout', () => {
    test('header Total Amount rata kanan dan jarak ke tanggal >= 16px di 1440', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(page, '/budgets', '/api/budgets');

        await expectNumericHeaderRightAligned(page, 'Total Amount');

        const head = page.locator('th', { hasText: 'Total Amount' }).first();
        const index = await columnIndexOf(head);
        const amountCell = page
            .locator(`tbody tr:first-child td:nth-child(${index})`)
            .first();
        const dateCell = page
            .locator(`tbody tr:first-child td:nth-child(${index + 1})`)
            .first();
        const valueBox = await boxOf(amountCell.locator('div').first());
        const dateBox = await boxOf(dateCell);
        const gap = dateBox.x - (valueBox.x + valueBox.width);
        expect(gap).toBeGreaterThanOrEqual(16);
    });

    test('sel sebelum kolom aksi tidak tertutup dan indikator scroll tampil di 390', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await openModule(page, '/budgets', '/api/budgets');

        await expect(
            page
                .locator('[data-slot="table-container"][data-overflow-right]')
                .first(),
        ).toBeVisible();
        await expectRoomBeforeActions(page);
    });
});

test.describe('Bank reconciliations - kolom angka & layout', () => {
    test('header angka Statement, Book, Difference rata kanan di 1440', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await openModule(
            page,
            '/bank-reconciliations',
            '/api/bank-reconciliations',
        );

        await expectNumericHeaderRightAligned(page, 'Statement Balance');
        await expectNumericHeaderRightAligned(page, 'Book Balance');
        await expectNumericHeaderRightAligned(page, 'Difference');
    });

    test('sel sebelum kolom aksi tidak tertutup di 390', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await openModule(
            page,
            '/bank-reconciliations',
            '/api/bank-reconciliations',
        );
        await expectRoomBeforeActions(page);
    });
});
