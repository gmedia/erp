import { expect, test } from '@playwright/test';
import { login, reloadAndWaitForApi } from '../helpers';
import { createCustomerInvoice } from './helpers';

test.describe('Customer invoice cancel action (posted)', () => {
    test('batal invoice terposting dari menu baris', async ({ page }) => {
        await login(page);
        await page.goto('/customer-invoices');

        const invoiceNumber = await createCustomerInvoice(page);

        const sent = await page.evaluate(async (number) => {
            const token = localStorage.getItem('api_token') || '';
            const headers = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${token}`,
            };
            const list = await fetch(
                `/api/customer-invoices?search=${encodeURIComponent(number)}&per_page=5`,
                { headers },
            );
            const found = ((await list.json()).data || []).find(
                (row: { invoice_number: string }) => row.invoice_number === number,
            );
            const detail = await fetch(`/api/customer-invoices/${found.id}`, { headers });
            const current = (await detail.json()).data;
            const response = await fetch(`/api/customer-invoices/${found.id}`, {
                method: 'PUT',
                headers,
                body: JSON.stringify({ status: 'sent', items: current.items.map((item: Record<string, unknown>) => ({
                    account_id: item.account_id,
                    description: item.description,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    discount_percent: item.discount_percent ?? 0,
                    tax_percent: item.tax_percent ?? 0,
                })) }),
            });
            return response.ok;
        }, invoiceNumber);
        expect(sent).toBeTruthy();

        await reloadAndWaitForApi(page, '/api/customer-invoices');

        const row = page.locator('tbody tr', { hasText: invoiceNumber }).first();
        await expect(row).toBeVisible({ timeout: 15000 });
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Cancel Invoice' }).click();

        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toContainText(/will be cancelled/i);

        const cancelled = page.waitForResponse(
            (r) =>
                r.url().includes('/api/customer-invoices/') &&
                r.request().method() === 'PUT',
        );
        await dialog.getByRole('button', { name: 'Yes, cancel' }).click();
        expect((await cancelled).status()).toBeLessThan(400);

        await expect(dialog).toBeHidden();
        await expect(row).toContainText('Cancelled');
    });
});
