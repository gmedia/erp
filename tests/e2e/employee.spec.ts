import { test, expect, Page } from '@playwright/test';

// Helper to perform login
async function login(page: Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'admin@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button:has-text("Log in")');
  await page.waitForURL('**/dashboard');
}

// Helper to navigate to the employees page
async function goToEmployees(page: Page): Promise<void> {
  await page.goto('/employees');
  await page.waitForSelector('button:has-text("Add")');
}

// Typed data for employee form
type EmployeeFormData = {
  name: string;
  email: string;
  phone: string;
  department: string;
  position: string;
  salary: string;
};

// Helper to fill the employee form using shadcn/ui Select components
async function fillEmployeeForm(page: Page, data: EmployeeFormData): Promise<void> {
  // Name
  await page.fill('input[placeholder="John Doe"]', data.name);
  // Email
  await page.fill('input[placeholder="john.doe@example.com"]', data.email);
  // Phone
  await page.fill('input[placeholder="+1 (555) 123-4567"]', data.phone);
  // Department (shadcn Select)
  await page.waitForSelector('button:has-text("Select a department")', { timeout: 10000 });
  await page.click('button:has-text("Select a department")');
  await page.getByRole('option', { name: data.department, exact: true }).click();
  // Position (shadcn Select)
  await page.waitForSelector('button:has-text("Select a position")', { timeout: 10000 });
  await page.click('button:has-text("Select a position")');
  await page.getByRole('option', { name: data.position, exact: true }).click();
  // Salary
  await page.fill('input[placeholder="50000.00"]', data.salary);
  // Ensure any pending network activity finishes before proceeding
  await page.waitForLoadState('networkidle');
}

// Test suite for Employee module
test.describe('Employee module e2e tests', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await goToEmployees(page);
    await expect(page).toHaveURL(/.*\/employees/);
  });

  test('Add new employee', async ({ page }) => {
    await page.click('button:has-text("Add")');
    await page.waitForSelector('input[placeholder="John Doe"]');

    await fillEmployeeForm(page, {
      name: 'John Doe',
      email: 'john.doe@example.com',
      phone: '+1 (555) 123-4567',
      department: 'Sales',
      position: 'Junior',
      salary: '50000',
    });

    // Submit the form (Add button inside the dialog)
    await page.getByRole('button', { name: 'Add' }).nth(1).click();

    // Verify the new row appears
    await page.waitForSelector('tr:has-text("John Doe")');
    await expect(page.locator('tr:has-text("John Doe")')).toBeVisible();
  });

  test('Edit employee', async ({ page }) => {
    const row = page.locator('tr', { hasText: 'John Doe' });
    await expect(row).toBeVisible();
    await row.locator('button:has-text("Edit")').click();

    await page.waitForSelector('input[placeholder="John Doe"]');
    await fillEmployeeForm(page, {
      name: 'John Smith',
      email: 'john.smith@example.com',
      phone: '+1 (555) 987-6543',
      department: 'Engineering',
      position: 'Senior',
      salary: '60000',
    });

    // Submit the form (Update button)
    await page.getByRole('button', { name: 'Update' }).click();

    // Verify the updated name appears
    await expect(page.locator('tr:has-text("John Smith")')).toBeVisible();
  });

  test('Delete employee', async ({ page }) => {
    const row = page.locator('tr', { hasText: 'John Smith' });
    await expect(row).toBeVisible();
    await row.locator('button:has-text("Delete")').click();

    // Confirm deletion
    await page.getByRole('button', { name: 'Confirm' }).click();

    // Verify the row is gone
    await expect(row).not.toBeVisible();
  });

  test('Search employee', async ({ page }) => {
    const searchInput = page.locator('input[placeholder="Search employees..."]');
    await expect(searchInput).toBeVisible();
    await searchInput.fill('John Doe');
    await searchInput.press('Enter');

    const rows = page.locator('tbody tr');
    // Wait for the table to reflect the filtered result (no count assertion)
    await expect(rows.first()).toContainText('John Doe');
  });

  test('Filter employee by department', async ({ page }) => {
    await page.click('button:has-text("Filters")');
    
    // Department filter (shadcn Select)
    await page.waitForSelector('button:has-text("All departments")', { timeout: 10000 });
    await page.click('button:has-text("All departments")');
    await page.getByRole('option', { name: 'Engineering', exact: true }).click();

    // Apply filters
    await page.getByRole('button', { name: 'Apply Filters' }).click();

    const rows = page.locator('tbody tr');
    // Ensure at least one row matches the filter
    await expect(rows).toHaveCount(1);
    await expect(rows.first()).toContainText('Engineering');
  });

  test('Export employee data', async ({ page }) => {
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.click('button:has-text("Export")'),
    ]);

    // Verify the downloaded file has .xlsx extension
    const suggested = download.suggestedFilename();
    expect(suggested).toMatch(/\.xlsx$/);
  });
});