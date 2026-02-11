import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Search', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should search by model name', async ({ page }) => {
        // Wait for table to load
        await expect(page.locator('table')).toBeVisible();

        // Type in search box
        const searchInput = page.getByPlaceholder('Search by model name or manufacturer...');
        await searchInput.fill('Dell');
        
        const searchResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.url().includes('search=') &&
            resp.status() === 200
        );
        await searchInput.press('Enter');
        await searchResponse;

        // Verify waiting for network idle or some loading state if applicable
        // But usually waiting for table content update is better
        await expect(page.locator('tbody tr')).toContainText('Dell');
        
        // Clear search
        await searchInput.fill('');
        await searchInput.press('Enter');
    });

    test('should search by manufacturer', async ({ page }) => {
        // Create a model to search for
        await page.getByRole('button', { name: 'Add' }).click();
        await page.getByLabel('Model Name').fill('Searchable Model');
        await page.getByLabel('Manufacturer').fill('Toyota Searchable');
        await page.getByRole('combobox').click();
        await page.getByRole('option').first().click();
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();

        // Debug: Check if it's visible in the list (refresh to be safe)
        const reloadResponse = page.waitForResponse(resp => {
            if (resp.url().includes('/api/asset-models') && resp.status() === 200) {
                return true;
            }
            return false;
        });   
        await page.reload();
        await reloadResponse;
        
        await expect(page.locator('table')).toBeVisible();
        // Skip visibility check here as it might be on page 2
        
        // Type in search box - Search by Model Name first
        const searchInput = page.getByPlaceholder('Search by model name or manufacturer...');
        await searchInput.fill('Searchable Model');
        
        // Wait for API response with search param
        const searchResponseName = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.url().includes('search=') &&
            resp.status() === 200
        );
        await searchInput.press('Enter');
        await searchResponseName;

        // Verify results for Model Name
        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();
        await expect(firstRow).toContainText('Searchable Model');
        
        // Clear search to reset state
        await searchInput.fill('');
        const clearResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.status() === 200
        );
        await searchInput.press('Enter');
        await clearResponse;

        // Now search by Manufacturer
        await searchInput.fill('Toyota Searchable');
        const searchResponseMfg = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.url().includes('search=') &&
            resp.status() === 200
        );
        await searchInput.press('Enter');
        await searchResponseMfg;

        // Verify results for Manufacturer search -> Should show the model
        const mfgResultRow = page.locator('tbody tr').first();
        await expect(mfgResultRow).toBeVisible();
        await expect(mfgResultRow).toContainText('Searchable Model');
    });
});
