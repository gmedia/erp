import { test, expect } from '@playwright/test';
import { createFiscalYear } from '../helpers';

test.describe('Fiscal Year DataTable Checkbox', () => {
    test('should have checkbox on row body but not on row head', async ({ page }) => {
        // Ensure data exists so we have rows
        await createFiscalYear(page);

        // Check for checkbox in table header (should not exist)
        // Adjust selector: typically header checkboxes are in `thead th`
        const headerCheckbox = page.locator('thead tr th').getByRole('checkbox');
        await expect(headerCheckbox).not.toBeVisible();

        // Check for checkbox in table body (should exist)
        // We know we just created one, so at least one row exists.
        const firstRowCheckbox = page.locator('tbody tr').first().getByRole('checkbox');
        await expect(firstRowCheckbox).toBeVisible();
    });
});
