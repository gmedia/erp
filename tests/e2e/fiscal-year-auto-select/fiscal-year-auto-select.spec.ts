import { expect, test, type Page } from '@playwright/test';
import { login } from '../helpers';

interface FiscalYearAutoSelectCase {
    readonly label: string;
    readonly route: string;
    readonly addDialogTitle: RegExp;
}

const cases: readonly FiscalYearAutoSelectCase[] = [
    {
        label: 'AP Payment',
        route: '/ap-payments',
        addDialogTitle: /Add New AP Payment/i,
    },
    {
        label: 'AR Receipt',
        route: '/ar-receipts',
        addDialogTitle: /Add AR Receipt/i,
    },
    {
        label: 'Period Closing',
        route: '/period-closings',
        addDialogTitle: /Add New Period Closing/i,
    },
    {
        label: 'Bank Reconciliation',
        route: '/bank-reconciliations',
        addDialogTitle: /Add New Bank Reconciliation/i,
    },
];

async function openAddDialog(page: Page, dialogTitle: RegExp): Promise<void> {
    const fiscalYearsResponse = page.waitForResponse(
        (r) => r.url().includes('/api/fiscal-years') && r.status() < 400,
        { timeout: 15000 },
    );

    const addButton = page.getByRole('button', { name: /^Add$/i }).first();
    await expect(addButton).toBeVisible({ timeout: 15000 });
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: dialogTitle });
    await expect(dialog).toBeVisible({ timeout: 10000 });

    await fiscalYearsResponse;
}

test.describe('Fiscal Year auto-select on financial transaction forms', () => {
    for (const fyCase of cases) {
        test(`${fyCase.label} form auto-selects preferred fiscal year`, async ({
            page,
        }) => {
            test.setTimeout(60000);

            await login(page, undefined, undefined, {
                requireDashboard: false,
            });
            await page.goto(fyCase.route);
            await page.waitForResponse(
                (r) =>
                    r.url().includes(fyCase.route.replace(/^\//, '/api/')) &&
                    r.status() < 400,
                { timeout: 15000 },
            );

            await openAddDialog(page, fyCase.addDialogTitle);

            const dialog = page.getByRole('dialog', {
                name: fyCase.addDialogTitle,
            });
            const trigger = dialog.getByRole('combobox', {
                name: /Fiscal Year/i,
            });
            await expect(trigger).toBeVisible();

            await expect
                .poll(async () => (await trigger.textContent())?.trim() ?? '', {
                    message: `Fiscal year was not auto-selected on ${fyCase.label} form (still showing placeholder)`,
                    timeout: 15000,
                    intervals: [250, 500, 1000],
                })
                .not.toMatch(/^select/i);

            const finalText = (await trigger.textContent())?.trim() ?? '';
            expect(finalText.length).toBeGreaterThan(0);
        });
    }
});
