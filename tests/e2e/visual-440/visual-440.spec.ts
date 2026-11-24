import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Visual #440 wave 8 mobile fixes', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('users 390: email tidak tertutup pita pudar, aksi sticky', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const usersResponse = page.waitForResponse(
            (r) => r.url().includes('/api/users') && r.status() < 400,
        );
        await page.goto('/users');
        await usersResponse;

        const row = page.getByTestId('user-row').first();
        await expect(row).toBeVisible();

        const actionCell = row.locator('td').last();
        const position = await actionCell.evaluate(
            (el) => getComputedStyle(el).position,
        );
        expect(position).toBe('sticky');
    });

    test('trial-balance 390: kolom Credit tidak tertutup fade tepi', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const reportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                r.status() < 400,
        );
        await page.goto('/reports/trial-balance');
        await reportResponse;

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
            (r) => r.url().includes('/api/accounts') && r.status() < 400,
        );
        await page.goto('/accounts');
        await accountsResponse;

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
                r.status() < 400,
        );
        await page.goto('/reports/balance-sheet');
        const base = await (await baseResponse).json();
        const years: { id: number }[] = base.fiscalYears ?? [];
        test.skip(years.length < 2, 'Butuh minimal dua tahun fiskal di seed');

        const compareResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/balance-sheet') &&
                r.url().includes('comparison_year_id') &&
                r.status() < 400,
        );
        const comparisonId = years.find(
            (y) => y.id !== base.selectedYearId,
        )!.id;
        await page.goto(
            `/reports/balance-sheet?fiscal_year_id=${base.selectedYearId}` +
                `&comparison_year_id=${comparisonId}`,
        );
        await compareResponse;

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

    test('financial-dashboard 390: nilai KPI tidak terpotong kartu', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const summaryResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/financial-dashboard') &&
                r.status() < 400,
        );
        await page.goto('/financial-dashboard');
        await summaryResponse;

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
                r.url().includes('/api/asset-categories') && r.status() < 400,
        );
        await page.goto('/asset-categories');
        await listResponse;

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
                r.url().includes('/api/account-mappings') && r.status() < 400,
        );
        await page.goto('/account-mappings');
        await listResponse;

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
                r.url().includes('/api/asset-categories') && r.status() < 400,
        );
        await page.goto('/asset-categories');
        await listResponse;

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
                r.url().includes('/api/asset-categories') && r.status() < 400,
        );
        await page.goto('/asset-categories');
        await listResponse;

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
                r.url().includes('/api/stock-movements') && r.status() < 400,
        );
        await page.goto('/stock-movements');
        await listResponse;

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
                r.status() < 400,
        );
        await page.goto('/report-configurations');
        await listResponse;

        const firstHeader = page.locator('thead th').nth(1);
        await expect(firstHeader).toContainText('Name');
    });

    test('ar-outstanding: kolom uang rata kanan', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 900 });
        const reportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/ar-outstanding') &&
                r.status() < 400,
        );
        await page.goto('/reports/ar-outstanding');
        await reportResponse;

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
    });
});
