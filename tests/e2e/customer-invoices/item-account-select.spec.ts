import { expect, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';
import { createCustomerInvoice } from './helpers';

test.describe('Customer Invoice item account select', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/customer-invoices') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/customer-invoices');
        await listed;
    });

    test('edit dokumen lama menampilkan label akun pada item', async ({ page }) => {
        const invoiceNumber = await createCustomerInvoice(page);
        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            invoiceNumber,
            '/api/customer-invoices',
        );
        const row = page.locator('tbody tr').filter({ hasText: invoiceNumber }).first();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await formDialog.getByRole('button', { name: 'Edit item 1' }).click();

        const itemDialog = page.getByRole('dialog').last();
        const accountSelect = itemDialog.getByRole('combobox', { name: 'Account' });
        await expect(accountSelect).not.toContainText('Select Account');
    });

    test('akun dipilih lewat select di Add Item', async ({ page }) => {
        const invoiceNumber = await createCustomerInvoice(page);
        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            invoiceNumber,
            '/api/customer-invoices',
        );
        const row = page.locator('tbody tr').filter({ hasText: invoiceNumber }).first();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const formDialog = page.getByRole('dialog').first();
        await formDialog.getByRole('button', { name: 'Add Item' }).click();

        const itemDialog = page.getByRole('dialog').last();
        const accountSelect = itemDialog.getByRole('combobox', { name: 'Account' });
        await expect(accountSelect).toContainText('Select Account');

        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/accounts') && r.ok(),
        );
        await accountSelect.click();
        await listed;

        const option = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button')
            .first();
        await expect(option).toBeVisible();
        const optionText = (await option.textContent())?.trim() ?? '';
        expect(optionText).toMatch(/^.+ - .+/);
        await option.click();

        await expect(accountSelect).toContainText(optionText);
    });
});
