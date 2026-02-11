import { test, expect } from '@playwright/test';
import { createAccountMapping } from './helpers';
import { login } from '../helpers';

test.describe('Account Mapping Checkboxes', () => {
    test('should have checkbox on row body but not on row head', async ({ page }) => {
        // 1. Login
        await login(page);

        // 2. Ensure we have data
        await createAccountMapping(page);

        // 3. Navigate to Account Mappings (helper does it, but good to be explicit if helper behavior changes)
        // createAccountMapping goes to /account-mappings already.

        // 4. Check that header checkbox does NOT exist
        const headerCheckboxRole = page.getByRole('checkbox', { name: /Select all/i });
        await expect(headerCheckboxRole).not.toBeVisible();

        // 5. Check that body checkbox DOES exist
        const rowCheckbox = page.locator('tbody tr').first().getByRole('checkbox');
        await expect(rowCheckbox).toBeVisible();
        await rowCheckbox.click();
        await expect(rowCheckbox).toBeChecked();
    });
});
