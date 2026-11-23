import { expect, test } from '@playwright/test';

import { login } from '../helpers';
import { createJournalEntry } from '../journal-entries/helpers';
import { formatIdr, getFiscalYearId, readReportTotals } from './helpers';

/**
 * Record-to-report (issue #355).
 *
 * Manual journal -> post -> trial balance / balance sheet / income statement.
 * Numbers are asserted as before/after deltas so the test does not depend on
 * seed balances (#342, #345 change the seed).
 *
 * Journal: Dr Cash (11100) / Cr Sales Revenue (41000), balanced and posted.
 * Expected: trial balance stays balanced; assets rise by the amount; revenue
 * rises by the amount; net income rises by the amount; the balance sheet
 * still balances (assets = liabilities + equity) after the change.
 */
test.describe('Record-to-report: jurnal manual sampai laporan keuangan', () => {
    test.setTimeout(240000);

    test('posting jurnal manual menggeser TB, neraca, dan laba rugi sebesar nilai jurnal', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });

        const fiscalYearId = await getFiscalYearId(page);
        const before = await readReportTotals(page, fiscalYearId);

        // Unique, non-round amount so an unrelated posting cannot match it.
        const amount = 1234567 + (Date.now() % 1000);
        const description = `E2E RTR ${Date.now()}`;

        // 1. Create the journal through the UI (balanced draft).
        await page.goto('/journal-entries');
        await createJournalEntry(page, {
            description,
            lines: [
                {
                    account: 'Cash in Banks',
                    debit: String(amount),
                    credit: '0',
                    memo: 'RTR debit',
                },
                {
                    account: 'Sales Revenue',
                    debit: '0',
                    credit: String(amount),
                    memo: 'RTR credit',
                },
            ],
        });

        // 2. Post it through the UI (Posting Journals -> Post Selected).
        await page.goto('/posting-journals');
        await page.waitForLoadState('networkidle');
        const searchInput = page.getByPlaceholder(/Search journals\.\.\./i);
        await searchInput.fill(description);
        await page.waitForLoadState('networkidle');

        const row = page.locator('tr').filter({ hasText: description });
        await expect(row).toBeVisible({ timeout: 15000 });
        await row.locator('button[role="checkbox"]').click();

        const postButton = page.getByRole('button', { name: /Post Selected/i });
        await expect(postButton).toBeEnabled();
        await postButton.click();
        await expect(row).not.toBeVisible({ timeout: 15000 });

        // 3. Read the reports after posting and compare deltas.
        const after = await readReportTotals(page, fiscalYearId);

        const deltaTrialDebit = after.trialDebit - before.trialDebit;
        const deltaTrialCredit = after.trialCredit - before.trialCredit;
        const deltaAssets = after.assets - before.assets;
        const deltaRevenue = after.revenue - before.revenue;
        const deltaNetIncome = after.netIncome - before.netIncome;

        // Trial balance: the journal itself is balanced, so debit and credit
        // grow by the same amount.
        expect(
            deltaTrialDebit,
            `TB debit delta harus = ${formatIdr(amount)}`,
        ).toBeCloseTo(amount, 2);
        expect(
            deltaTrialCredit,
            `TB credit delta harus = ${formatIdr(amount)}`,
        ).toBeCloseTo(amount, 2);
        expect(
            after.trialDebit - after.trialCredit,
            'trial balance harus balance setelah posting',
        ).toBeCloseTo(0, 2);

        // Balance sheet: cash up by the amount. Equity already includes current
        // year earnings, so assets must equal liabilities + equity.
        expect(
            deltaAssets,
            `kenaikan aset harus = ${formatIdr(amount)}`,
        ).toBeCloseTo(amount, 2);
        expect(
            after.assets - (after.liabilities + after.equity),
            'neraca harus balance (aset = liabilitas + ekuitas)',
        ).toBeCloseTo(0, 2);

        // Income statement: revenue and net income rise by the amount.
        expect(
            deltaRevenue,
            `kenaikan pendapatan harus = ${formatIdr(amount)}`,
        ).toBeCloseTo(amount, 2);
        expect(
            deltaNetIncome,
            `kenaikan laba bersih harus = ${formatIdr(amount)}`,
        ).toBeCloseTo(amount, 2);
    });
});
