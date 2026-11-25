import { expect, test } from '@playwright/test';

import { openModule } from '../helpers';
import { createSupplierBill, searchSupplierBill } from './helpers';

test.describe('Supplier Bill amount due for draft', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/supplier-bills', '/api/supplier-bills');
    });

    test('draft row shows dash in Amount Due', async ({ page }) => {
        const billNumber = await createSupplierBill(page);
        await searchSupplierBill(page, billNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: billNumber })
            .first();
        await expect(row).toBeVisible();

        const amountDueIndex = await page
            .locator('thead th')
            .evaluateAll((headers) =>
                headers.findIndex((th) =>
                    (th.textContent ?? '').includes('Amount Due'),
                ),
            );
        expect(amountDueIndex).toBeGreaterThanOrEqual(0);

        await expect(row.locator('td').nth(amountDueIndex)).toHaveText('-');
    });
});
