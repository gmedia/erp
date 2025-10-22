import { test, expect } from '@playwright/test';
import { createEmployee, searchEmployee } from '../helpers';

test('add multiple employees end‑to‑end', async ({ page }) => {
  // Create three employees, collecting their emails
  const emails: string[] = [];

  for (let i = 0; i < 3; i++) {
    const email = await createEmployee(page);
    emails.push(email);
  }

  // Verify each employee appears in the list
  for (const email of emails) {
    // Optionally ensure the employee can be found via the search helper
    await searchEmployee(page, email);
    await expect(page.locator(`text=${email}`)).toBeVisible();
  }
});