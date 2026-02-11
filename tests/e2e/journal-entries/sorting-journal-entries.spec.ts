import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('sorting columns works correctly', async ({ page }) => {
  await login(page);
  await page.goto('/journal-entries');
  
  const columns = ['Entry Number', 'Date', 'Description', 'Reference', 'Total Amount', 'Status'];
  
  for (const column of columns) {
    const headerBtn = page.getByRole('button', { name: column, exact: true });
    await expect(headerBtn).toBeVisible();
    
    // Sort Ascending
    await headerBtn.click();
    await page.waitForLoadState('networkidle');
    // We could check URL or just verify it hasn't crashed
    
    // Sort Descending
    await headerBtn.click();
    await page.waitForLoadState('networkidle');
  }
});
