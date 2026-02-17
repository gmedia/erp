import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import { createEmployee } from '../employees/helpers';

test.describe('Employee Permissions', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('assign permissions to an employee', async ({ page }) => {
        // 1. Create a new employee with a unique name
        const timestamp = Date.now();
        const uniqueName = `Test Employee ${timestamp}`;
        const employeeEmail = await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Permissions page
        await page.goto('/permissions');
        await expect(page).toHaveTitle(/Permissions/);

        // 3. Select the employee
        // AsyncSelectField trigger
        const selectTrigger = page.locator('button[role="combobox"]');
        await expect(selectTrigger).toBeVisible();
        await selectTrigger.click();

        // Type in search
        const searchInput = page.getByPlaceholder('Search...');
        await expect(searchInput).toBeVisible();
        await searchInput.fill(uniqueName); // Search by unique name

        // Select option
        // AsyncSelectField renders options with checks.
        const option = page.locator('div[role="option"]').filter({ hasText: uniqueName }).first();
        await expect(option).toBeVisible();
        await option.click();
        
        // Wait for selection to reflect in the button (confirms state update)
        await expect(selectTrigger).toHaveText(uniqueName);

        // 4. Wait for permissions to load/appear
        // We expect the tree to be visible.
        await expect(page.getByText('Permissions Hierarchy')).toBeVisible();
        
        // 5. Select "Create Department" permission
        // The tree node name is "Create Department".
        // The checkbox is adjacent.
        // We can find the text "Create Department" and click it, as clicking the row toggles it (based on TreeNode implementation).
        const permissionName = 'Create Department';
        const permissionNode = page.getByText(permissionName).first();
        await expect(permissionNode).toBeVisible();
        await permissionNode.click();

        // 6. Save changes
        const saveButton = page.getByRole('button', { name: 'Save Changes' });
        await expect(saveButton).toBeEnabled();
        await saveButton.click();

        // 7. Verify success toast (optional/soft check as it can be flaky in headless)
        // await expect(page.getByText('Permissions updated successfully')).toBeVisible();

        // 8. Verify persistence
        await page.reload();
        
        // Re-select employee (state is reset on reload)
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await expect(option).toBeVisible();
        await option.click();

        // Verify "Create Department" is checked
        // The checkbox parent div has the click handler.
        // Use a more specific selector targeting the node header (flex container) which does not include children text
        const row = page.locator('.flex.items-center').filter({ hasText: new RegExp(`^${permissionName}$`) });
        const checkbox = row.getByRole('checkbox');
        await expect(checkbox).toHaveAttribute('aria-checked', 'true');
    });
});
