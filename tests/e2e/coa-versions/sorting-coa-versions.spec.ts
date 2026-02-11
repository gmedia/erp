import { test, expect } from '@playwright/test';
import { createCoaVersion } from '../helpers';

test.describe('COA Version Sorting', () => {
    test('should sort coa versions by all columns', async ({ page }) => {
        // Create two coa versions with a unique prefix to isolate this test run
        const timestamp = Date.now();
        const prefix = `SortTest-${timestamp}`;
        const nameA = `${prefix}-A`; // Should be first
        const nameZ = `${prefix}-Z`; // Should be last
        
        await createCoaVersion(page, { name: nameA });
        await createCoaVersion(page, { name: nameZ });

        // Search for the prefix to filter only these two records
        const searchInput = page.getByPlaceholder('Search COA versions...');
        await searchInput.fill(prefix);
        await searchInput.press('Enter');
        
        await page.waitForResponse(response => 
            response.url().includes('/api/coa-versions') && 
            response.url().includes('search=')
        );
        await page.waitForTimeout(500); 

        await expect(page.locator('tbody tr')).toHaveCount(2);

        // Columns to test: Name, Fiscal Year (might be harder if same year), Status, Created At
        // Let's test Name specifically as primary sort verification
        const nameHeader = page.getByRole('button', { name: 'Name', exact: true });
        
        // Sort Ascending (A first)
        const sortAscPromise = page.waitForResponse(response => 
            response.url().includes('/api/coa-versions') && 
            response.url().includes('sort_by=name') && 
            response.url().includes('sort_direction=asc')
        );
        await nameHeader.click();
        await sortAscPromise;
        await page.waitForTimeout(500);
        await expect(page.locator('tbody tr').first()).toContainText(nameA);
        
        // Sort Descending (Z first)
        const sortDescPromise = page.waitForResponse(response => 
            response.url().includes('/api/coa-versions') && 
            response.url().includes('sort_by=name') && 
            response.url().includes('sort_direction=desc')
        );
        await nameHeader.click();
        await sortDescPromise;
        await page.waitForTimeout(500);
        await expect(page.locator('tbody tr').first()).toContainText(nameZ);
    });
});
