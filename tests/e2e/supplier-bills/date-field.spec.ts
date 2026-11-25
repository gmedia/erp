import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { createSupplierBill, searchSupplierBill } from './helpers';

test.describe('Supplier Bill date fields', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/supplier-bills', '/api/supplier-bills');
    });

    test('can change bill and due dates', async ({ page }) => {
        const billNumber = await createSupplierBill(page);
        await searchSupplierBill(page, billNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: billNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Supplier Bill/i,
        });
        await expect(dialog).toBeVisible();

        const billDate = await changeDateFieldValue(page, dialog, 'Bill Date');
        const dueDate = await changeDateFieldValue(page, dialog, 'Due Date');

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/supplier-bills/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/supplier-bills/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            bill_date?: string;
            due_date?: string;
        };

        expect(payload.bill_date).toBe(billDate);
        expect(payload.due_date).toBe(dueDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
