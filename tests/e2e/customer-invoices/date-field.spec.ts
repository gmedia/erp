import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { createCustomerInvoice, searchCustomerInvoice } from './helpers';

test.describe('Customer Invoice date fields', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/customer-invoices', '/api/customer-invoices');
    });

    test('can change invoice and due dates', async ({ page }) => {
        const invoiceNumber = await createCustomerInvoice(page);
        await searchCustomerInvoice(page, invoiceNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: invoiceNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Customer Invoice/i,
        });
        await expect(dialog).toBeVisible();

        const invoiceDate = await changeDateFieldValue(
            page,
            dialog,
            'Invoice Date',
        );
        const dueDate = await changeDateFieldValue(page, dialog, 'Due Date');

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/customer-invoices/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/customer-invoices/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            invoice_date?: string;
            due_date?: string;
        };

        expect(payload.invoice_date).toBe(invoiceDate);
        expect(payload.due_date).toBe(dueDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
