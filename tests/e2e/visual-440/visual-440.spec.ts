import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

test.describe('Visual #440 wave 8 mobile fixes', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('trial-balance 390: kolom Credit tidak tertutup fade tepi', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const reportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                r.request().method() === 'GET',
        );
        await page.goto('/reports/trial-balance');
        await expectResponseOk(await reportResponse);

        const container = page.locator('[data-slot="table-container"]').first();
        await expect(container).toBeVisible();
        const fade = await container.evaluate(
            (el) => getComputedStyle(el, '::after').backgroundImage,
        );
        expect(fade).toBe('none');
    });

    test('accounts 390: aksi baris tree terlihat tanpa hover', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const accountsResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/accounts') &&
                r.request().method() === 'GET',
        );
        await page.goto('/accounts');
        await expectResponseOk(await accountsResponse);

        const addChild = page.locator('button:has(svg.lucide-plus)').first();
        test.skip(
            (await addChild.count()) === 0,
            'Tidak ada akun di data seed',
        );
        await expect(addChild).toBeVisible();
        const opacity = await addChild.evaluate(
            (el) => getComputedStyle(el.parentElement as HTMLElement).opacity,
        );
        expect(opacity).toBe('1');
    });

    test('balance-sheet 390: kolom nilai tidak terpotong dan sejajar', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const baseResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                r.request().method() === 'GET',
        );
        await page.goto('/reports/balance-sheet');
        const baseRes = await baseResponse;
        await expectResponseOk(baseRes);
        const base = await baseRes.json();
        const years: { id: number }[] = base.fiscalYears ?? [];
        test.skip(years.length < 2, 'Butuh minimal dua tahun fiskal di seed');

        const compareResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                r.url().includes('comparison_year_id') &&
                r.request().method() === 'GET',
        );
        const comparisonId = years.find(
            (y) => y.id !== base.selectedYearId,
        )!.id;
        await page.goto(
            `/reports/balance-sheet?fiscal_year_id=${base.selectedYearId}` +
                `&comparison_year_id=${comparisonId}`,
        );
        await expectResponseOk(await compareResponse);

        const header = page.getByText('Comparison', { exact: true }).first();
        await expect(header).toBeVisible();

        const scroller = await header.evaluate((el) => {
            let node: HTMLElement | null = el as HTMLElement;
            while (node && getComputedStyle(node).overflowX !== 'auto') {
                node = node.parentElement;
            }
            const box = node?.getBoundingClientRect();
            return box ? { right: box.right, width: box.width } : null;
        });
        expect(scroller).not.toBeNull();
        expect(scroller!.right).toBeLessThanOrEqual(390 + 1);
    });

    const comparisonReports = [
        { slug: 'balance-sheet', label: 'balance-sheet' },
        { slug: 'income-statement', label: 'income-statement' },
        { slug: 'comparative', label: 'comparative' },
    ] as const;

    for (const report of comparisonReports) {
        test(`${report.label} 390: setiap nilai Current masuk kartu`, async ({
            page,
        }) => {
            await page.setViewportSize({ width: 390, height: 844 });
            const baseResponse = page.waitForResponse(
                (r) =>
                    r.url().includes(`/api/reports/${report.slug}`) &&
                    r.request().method() === 'GET',
            );
            await page.goto(`/reports/${report.slug}`);
            const baseRes = await baseResponse;
            await expectResponseOk(baseRes);
            const base = await baseRes.json();
            const years: { id: number }[] = base.fiscalYears ?? [];
            test.skip(
                years.length < 2,
                'Butuh minimal dua tahun fiskal di seed',
            );

            const comparisonId = years.find(
                (y) => y.id !== base.selectedYearId,
            )!.id;
            const compareResponse = page.waitForResponse(
                (r) =>
                    r.url().includes(`/api/reports/${report.slug}`) &&
                    r.url().includes('comparison_year_id') &&
                    r.request().method() === 'GET',
            );
            await page.goto(
                `/reports/${report.slug}?fiscal_year_id=${base.selectedYearId}` +
                    `&comparison_year_id=${comparisonId}`,
            );
            await expectResponseOk(await compareResponse);

            const header = page.getByText('Current', { exact: true }).first();
            await expect(header).toBeVisible();

            const cells = await page
                .locator('[data-slot="card"]')
                .filter({ has: page.getByText('Current', { exact: true }) })
                .evaluateAll((cards) =>
                    cards.flatMap((card) => {
                        const cardRight = card.getBoundingClientRect().right;
                        return Array.from(
                            card.querySelectorAll<HTMLElement>(
                                '.tabular-nums > div:first-child',
                            ),
                        ).map((el) => ({
                            text: el.textContent?.trim() ?? '',
                            right: el.getBoundingClientRect().right,
                            cardRight,
                        }));
                    }),
                );
            expect(cells.length).toBeGreaterThan(0);
            const overflow = cells.filter(
                (c) => c.text === '' || c.right > c.cardRight + 1,
            );
            expect(overflow).toEqual([]);
        });
    }

    test('comparative 390: kolom nama lebar wajar dan tidak beririsan nilai', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const baseResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/comparative') &&
                r.request().method() === 'GET',
        );
        await page.goto('/reports/comparative');
        const baseRes = await baseResponse;
        await expectResponseOk(baseRes);
        const base = await baseRes.json();
        const years: { id: number }[] = base.fiscalYears ?? [];
        test.skip(years.length < 2, 'Butuh minimal dua tahun fiskal di seed');

        const comparisonId = years.find(
            (y) => y.id !== base.selectedYearId,
        )!.id;
        const compareResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/comparative') &&
                r.url().includes('comparison_year_id') &&
                r.request().method() === 'GET',
        );
        await page.goto(
            `/reports/comparative?fiscal_year_id=${base.selectedYearId}` +
                `&comparison_year_id=${comparisonId}`,
        );
        await expectResponseOk(await compareResponse);

        const header = page.getByText('Current', { exact: true }).first();
        await expect(header).toBeVisible();

        const rows = await page
            .locator('[data-slot="card"]')
            .filter({ has: page.getByText('Current', { exact: true }) })
            .locator('button.text-left')
            .evaluateAll((buttons) =>
                buttons.map((button) => {
                    const row = button.parentElement!;
                    const value = row.querySelector<HTMLElement>(
                        '.tabular-nums > div:first-child',
                    );
                    const nameBox = button.getBoundingClientRect();
                    const valueBox = value?.getBoundingClientRect();
                    return {
                        name: button.textContent?.trim() ?? '',
                        nameWidth: nameBox.width,
                        nameRight: nameBox.right,
                        rowHeight: row.getBoundingClientRect().height,
                        valueLeft: valueBox?.left ?? Number.NaN,
                    };
                }),
            );

        expect(rows.length).toBeGreaterThan(0);
        for (const row of rows) {
            expect(row.nameWidth, row.name).toBeGreaterThanOrEqual(100);
            expect(row.rowHeight, row.name).toBeLessThanOrEqual(100);
            expect(row.nameRight, row.name).toBeLessThanOrEqual(row.valueLeft);
        }
    });

    test('financial-dashboard 390: nilai KPI tidak terpotong kartu', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const summaryResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/financial-dashboard') &&
                r.request().method() === 'GET',
        );
        await page.goto('/financial-dashboard');
        await expectResponseOk(await summaryResponse);

        const values = page.locator('.tabular-nums.font-semibold').filter({
            hasText: 'Rp',
        });
        const count = await values.count();
        test.skip(count === 0, 'Tidak ada nilai KPI di data seed');

        for (let i = 0; i < count; i++) {
            const overflow = await values.nth(i).evaluate((el) => {
                const card = el.closest('[data-slot="card"]');
                if (!card) return false;
                return el.scrollWidth > card.clientWidth;
            });
            expect(overflow).toBe(false);
        }
    });

    test('asset-categories 390: angka Useful Life rata kanan', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/asset-categories') &&
                r.request().method() === 'GET',
        );
        await page.goto('/asset-categories');
        await expectResponseOk(await listResponse);

        const cell = page
            .locator('td')
            .filter({ has: page.locator('div.tabular-nums') })
            .first();
        test.skip(
            (await cell.count()) === 0,
            'Tidak ada kategori aset di data seed',
        );
        const align = await cell.evaluate((el) => {
            const div = el.querySelector('div') as HTMLElement;
            return getComputedStyle(div).textAlign;
        });
        expect(align).toBe('right');
    });

    test('account-mappings 390: notes terpotong dengan elipsis', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/account-mappings') &&
                r.request().method() === 'GET',
        );
        await page.goto('/account-mappings');
        await expectResponseOk(await listResponse);

        const notes = page.locator('td div.truncate[title]').first();
        test.skip(
            (await notes.count()) === 0,
            'Tidak ada account mapping di data seed',
        );
        const overflow = await notes.evaluate(
            (el) => getComputedStyle(el).textOverflow,
        );
        expect(overflow).toBe('ellipsis');
    });

    test('asset-categories 390: teks header sortable sejajar isi kolom', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/asset-categories') &&
                r.request().method() === 'GET',
        );
        await page.goto('/asset-categories');
        await expectResponseOk(await listResponse);

        const header = page.getByTestId('sort-code');
        test.skip(
            (await header.count()) === 0,
            'Header sortable tidak ada di tampilan ini',
        );
        const body = page.locator('tbody tr td').nth(1);
        const [hx, bx] = await Promise.all([
            header.evaluate((el) => el.getBoundingClientRect().left),
            body.evaluate((el) => el.getBoundingClientRect().left),
        ]);
        expect(Math.abs(hx - bx)).toBeLessThanOrEqual(2);
    });

    test('asset-categories 390: header Useful Life rata kanan seperti angkanya', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/asset-categories') &&
                r.request().method() === 'GET',
        );
        await page.goto('/asset-categories');
        await expectResponseOk(await listResponse);

        const header = page.getByRole('button', {
            name: /Default Useful Life/,
        });
        test.skip(
            (await header.count()) === 0,
            'Header Useful Life tidak ada di tampilan ini',
        );
        const [hx, bx] = await Promise.all([
            header.evaluate((el) => el.getBoundingClientRect().right),
            page
                .locator('tbody tr td div.tabular-nums')
                .first()
                .evaluate((el) => el.getBoundingClientRect().right),
        ]);
        expect(Math.abs(hx - bx)).toBeLessThanOrEqual(12);
    });

    test('stock-movements: referensi SR- ditautkan seperti ST/SA', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1280, height: 900 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-movements') &&
                r.request().method() === 'GET',
        );
        await page.goto('/stock-movements');
        await expectResponseOk(await listResponse);

        const srLink = page.locator('a[href^="/supplier-returns?search=SR-"]');
        test.skip(
            (await srLink.count()) === 0,
            'Tidak ada referensi SR- di data seed',
        );
        await expect(srLink.first()).toBeVisible();
    });

    test('report-configurations: nama tampil sebagai kolom utama', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1280, height: 900 });
        const listResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/report-configurations') &&
                r.request().method() === 'GET',
        );
        await page.goto('/report-configurations');
        await expectResponseOk(await listResponse);

        const firstHeader = page.locator('thead th').nth(1);
        await expect(firstHeader).toContainText('Name');
    });

    test('ar-outstanding: kolom uang rata kanan', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 900 });
        const reportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/ar-outstanding') &&
                r.request().method() === 'GET',
        );
        await page.goto('/reports/ar-outstanding');
        await expectResponseOk(await reportResponse);

        const amountCell = page
            .locator('td div.tabular-nums.text-right')
            .first();
        test.skip(
            (await amountCell.count()) === 0,
            'Tidak ada piutang outstanding di data seed',
        );
        const align = await amountCell.evaluate(
            (el) => getComputedStyle(el).textAlign,
        );
        expect(align).toBe('right');

        const daysHeader = page
            .locator('thead th', { hasText: 'Days Overdue' })
            .first();
        const daysCell = page
            .locator('tbody tr td div.tabular-nums.text-right')
            .last();
        test.skip(
            (await daysCell.count()) === 0,
            'Tidak ada baris Days Overdue di data seed',
        );
        const daysAlign = await daysCell.evaluate(
            (el) => getComputedStyle(el).textAlign,
        );
        expect(daysAlign).toBe('right');
        await expect(daysHeader).toBeVisible();
    });

    test('aging-dashboard: bucket Over 90 Days terlihat di dalam kartu', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 1280, height: 900 });
        const agingResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/aging-dashboard') &&
                r.request().method() === 'GET',
        );
        await page.goto('/aging-dashboard');
        await expectResponseOk(await agingResponse);

        const bucket = page.getByText('Over 90 Days').first();
        test.skip(
            (await bucket.count()) === 0,
            'Tidak ada data aging di data seed',
        );
        await expect(bucket).toBeInViewport();
    });

    test('depreciation-runs 390: kolom Status tidak tertutup aksi', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const runsResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/asset-depreciation-runs') &&
                r.request().method() === 'GET',
        );
        await page.goto('/asset-depreciation-runs');
        await expectResponseOk(await runsResponse);

        const statusHeader = page
            .locator('thead th', { hasText: 'Status' })
            .first();
        test.skip(
            (await statusHeader.count()) === 0,
            'Header Status tidak ada',
        );
        const row = page.locator('tbody tr').first();
        test.skip(
            (await row.count()) === 0,
            'Tidak ada depreciation run di data seed',
        );
        const cells = row.locator('td');
        const actionCell = cells.last();
        await expect(cells.nth(3)).toBeVisible();
        const overlapped = await row.evaluate((tr) => {
            const tds = Array.from(tr.querySelectorAll('td'));
            const action = tds[tds.length - 1].getBoundingClientRect();
            return tds
                .slice(0, -1)
                .some(
                    (td) => td.getBoundingClientRect().right > action.left + 1,
                );
        });
        expect(overlapped).toBe(false);
        await expect(actionCell).toBeVisible();
    });
});
