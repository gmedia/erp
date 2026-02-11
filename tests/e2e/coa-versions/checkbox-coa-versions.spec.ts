import { test, expect } from '@playwright/test';
import { login, createCoaVersion } from '../helpers';

test.describe('COA Version Checkboxes', () => {
    test('should have checkbox on row body but not on row head', async ({ page }) => {
        // 1. Login
        await login(page);

        // 2. Ensure we have data
        await createCoaVersion(page);

        // 3. Navigate to COA Versions
        await page.goto('/coa-versions');
        await page.waitForLoadState('networkidle');

        // 4. Check that header checkbox does NOT exist (or is hidden/disabled if that's the requirement, 
        // unlike Fiscal Year where we explicitly didn't want it. 
        // "test DataTable harus memiliki checkbox pada row body tetapi tidak boleh ada pada row head")
        const headerCheckbox = page.locator('thead input[type="checkbox"]'); // Or role='checkbox'
        // shadcn/ui header checkbox usually has aria-label="Select all"
        const headerCheckboxRole = page.getByRole('checkbox', { name: /Select all/i });
        
        await expect(headerCheckboxRole).not.toBeVisible();

        // 5. Check that body checkbox DOES exist
        // Shadcn UI checkbox is a button with role checkbox
        const rowCheckbox = page.locator('tbody tr').first().getByRole('checkbox');
        await expect(rowCheckbox).toBeVisible();
        await rowCheckbox.click();
        await expect(rowCheckbox).toBeChecked();
    });
});
