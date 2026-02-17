import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import { createEmployee } from '../employees/helpers';

test.describe('User Management', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('create and update user for an employee', async ({ page }) => {
        // 1. Create a new employee with a unique name
        const timestamp = Date.now();
        const uniqueName = `Test User Employee ${timestamp}`;
        const employeeEmail = await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page
        await page.goto('/users');
        await expect(page).toHaveTitle(/Users/);

        // 3. Select the employee using AsyncSelectField
        const selectTrigger = page.locator('button[role="combobox"]');
        await expect(selectTrigger).toBeVisible();
        await selectTrigger.click();

        // Type in search
        const searchInput = page.getByPlaceholder('Search...');
        await expect(searchInput).toBeVisible();
        await searchInput.fill(uniqueName);

        // Select option
        const option = page.locator('div[role="option"]').filter({ hasText: uniqueName }).first();
        await expect(option).toBeVisible();
        await option.click();
        
        // Wait for selection to reflect in the button
        await expect(selectTrigger).toHaveText(uniqueName);

        // 4. Verify form fields are auto-populated
        await expect(page.locator('input#name')).toBeVisible();
        await expect(page.locator('input#email')).toBeVisible();
        await expect(page.locator('input#password')).toBeVisible();

        // 5. Fill in user details
        const newUserName = `User ${timestamp}`;
        const newUserEmail = `user${timestamp}@test.com`;
        const newUserPassword = 'password123';

        await page.fill('input#name', newUserName);
        await page.fill('input#email', newUserEmail);
        await page.fill('input#password', newUserPassword);

        // 6. Save changes
        const saveButton = page.getByRole('button', { name: 'Save Changes' });
        await expect(saveButton).toBeEnabled();
        await saveButton.click();

        // 7. Wait for save to complete by waiting for button to be re-enabled (loading ends)
        // and password placeholder to change (indicates userExists changed to true)
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 8. Verify persistence by reloading and re-selecting
        await page.reload();
        
        // Re-select employee
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await expect(option).toBeVisible();
        await option.click();

        // Verify saved data is displayed
        await expect(page.locator('input#name')).toHaveValue(newUserName);
        await expect(page.locator('input#email')).toHaveValue(newUserEmail);
        // Password field should be empty (for security)
        await expect(page.locator('input#password')).toHaveValue('');
    });

    test('update existing user details', async ({ page }) => {
        // 1. Create a new employee and user first
        const timestamp = Date.now();
        const uniqueName = `Existing User Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page and create user
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(uniqueName);
        
        const option = page.locator('div[role="option"]').filter({ hasText: uniqueName }).first();
        await option.click();

        // Create user initially
        const initialName = `Initial User ${timestamp}`;
        const initialEmail = `initial${timestamp}@test.com`;
        
        await page.fill('input#name', initialName);
        await page.fill('input#email', initialEmail);
        await page.fill('input#password', 'password123');
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 3. Reload and update user
        await page.reload();
        
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await option.click();

        // Wait for form to load with existing data
        await expect(page.locator('input#name')).toHaveValue(initialName);

        // Update user details
        const updatedName = `Updated User ${timestamp}`;
        const updatedEmail = `updated${timestamp}@test.com`;
        
        await page.fill('input#name', updatedName);
        await page.fill('input#email', updatedEmail);
        // Leave password empty to keep existing
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete (button re-enabled)
        await expect(page.getByRole('button', { name: 'Save Changes' })).toBeEnabled({ timeout: 10000 });
        
        // Brief wait for state to settle
        await page.waitForTimeout(500);

        // 4. Verify update persisted
        await page.reload();
        
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await option.click();

        await expect(page.locator('input#name')).toHaveValue(updatedName);
        await expect(page.locator('input#email')).toHaveValue(updatedEmail);
    });

    test('shows validation error for duplicate email', async ({ page }) => {
        // 1. Create two employees
        const timestamp = Date.now();
        const employee1Name = `Duplicate Test 1 ${timestamp}`;
        const employee2Name = `Duplicate Test 2 ${timestamp}`;
        
        await createEmployee(page, { name: employee1Name });
        await createEmployee(page, { name: employee2Name });
        
        // 2. Navigate to Users page and create user for employee 1
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(employee1Name);
        
        const option1 = page.locator('div[role="option"]').filter({ hasText: employee1Name }).first();
        await option1.click();

        const sharedEmail = `shared${timestamp}@test.com`;
        
        await page.fill('input#name', `User 1 ${timestamp}`);
        await page.fill('input#email', sharedEmail);
        await page.fill('input#password', 'password123');
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 3. Try to use the same email for employee 2
        await selectTrigger.click();
        await searchInput.fill(employee2Name);
        
        const option2 = page.locator('div[role="option"]').filter({ hasText: employee2Name }).first();
        await option2.click();

        await page.fill('input#name', `User 2 ${timestamp}`);
        await page.fill('input#email', sharedEmail); // Same email as user 1
        await page.fill('input#password', 'password123');
        
        await page.getByRole('button', { name: 'Save Changes' }).click();

        // 4. Verify validation error is shown
        await expect(page.getByText('The email has already been taken')).toBeVisible({ timeout: 10000 });
    });

    test('shows password required error for new user', async ({ page }) => {
        // 1. Create a new employee
        const timestamp = Date.now();
        const uniqueName = `Password Required ${timestamp}`;
        await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(uniqueName);
        
        const option = page.locator('div[role="option"]').filter({ hasText: uniqueName }).first();
        await option.click();

        // 3. Try to save without password
        await page.fill('input#name', `User ${timestamp}`);
        await page.fill('input#email', `user${timestamp}@test.com`);
        // Leave password empty
        
        await page.getByRole('button', { name: 'Save Changes' }).click();

        // 4. Verify validation error is shown
        await expect(page.getByText('The password field is required')).toBeVisible({ timeout: 10000 });
    });
});

