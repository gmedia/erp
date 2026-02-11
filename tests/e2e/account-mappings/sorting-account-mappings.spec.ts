import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Account Mapping Sorting', () => {
    test('should sort account mappings by all columns', async ({ page }) => {
        await login(page);
        await page.goto('/account-mappings');
        await page.waitForLoadState('networkidle');

        // We can't easily create specific sortable data names like "AAAA" and "ZZZZ" because 
        // Account Mapping relies on existing Accounts (Seed Data).
        // Verification strategy:
        // 1. Click header
        // 2. Wait for API request with sort params
        // 3. Verify request checks out (implicitly verify UI triggers correct backend call)
        
        // Columns: Source Account, Target Account, Type, Created At
        
        const columns = [
             // ID is internal column ID from the generic datatable or defined in columns.tsx
            // In columns.tsx: id: 'source', header: 'Source Account'
            { name: 'Source Account', sortKey: 'source' }, 
            { name: 'Target Account', sortKey: 'target' },
            { name: 'Type', sortKey: 'type' },
            { name: 'Created At', sortKey: 'created_at' }
        ];

        for (const col of columns) {
            // Click the column header cell (th) to trigger sorting via DataTable's built-in handler.
            // Using getByRole('columnheader', { name: ... }) targets the th.
            // Note: createSortingHeader puts a button inside, but DataTableCore handles click on th.
            const header = page.getByRole('columnheader', { name: col.name, exact: true });
            
            // Sort Ascending
            // Use a comprehensive wait that logs if needed or matches partially
            const sortAscPromise = page.waitForResponse(response => {
                const url = response.url();
                if (url.includes('/api/account-mappings')) {
                    console.log(`Intercepted request: ${url}`);
                }
                return url.includes('/api/account-mappings') && 
                       url.includes(`sort_by=${col.sortKey}`) && 
                       url.includes('sort_direction=asc');
            });
            await header.click({ force: true });
            await sortAscPromise;
            
            // Sort Descending
            const sortDescPromise = page.waitForResponse(response => 
                response.url().includes('/api/account-mappings') && 
                response.url().includes(`sort_by=${col.sortKey}`) && 
                response.url().includes('sort_direction=desc')
            );
            await header.click();
            await sortDescPromise;
        }
    });
});
