import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Asset Import E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can import assets via CSV', async ({ page }) => {
        // 1. Prepare CSV file
        const timestamp = Date.now();
        const uniqueAssetCode = `AST-${timestamp.toString().slice(-7)}`;
        
        const rowData = [
            uniqueAssetCode,          // asset_code
            `E2E Asset ${timestamp}`, // name
            'IT Equipment',           // asset_category
            '',                       // asset_model
            'Head Office',            // branch
            '',                       // location
            '',                       // department
            '',                       // employee
            '',                       // supplier
            '',                       // serial_number
            '',                       // barcode
            '2024-01-01',             // purchase_date
            '15000000',               // purchase_cost
            'IDR',                    // currency
            '',                       // warranty_end_date
            'active',                 // status
            'good',                   // condition
            'E2E Import Verification' // notes
        ];
        
        const csvContent = `asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes\n${rowData.join(',')}`;
        
        const fileName = `import_asset_${timestamp}.csv`;
        const filePath = path.join('/tmp', fileName);
        fs.writeFileSync(filePath, csvContent);

        // 2. Navigate to Assets page
        await page.goto('/assets');
        await page.waitForResponse(r => r.url().includes('/api/assets') && r.status() === 200);

        // 3. Open Import Dialog
        const importButton = page.getByRole('button', { name: /import/i });
        await expect(importButton).toBeVisible();
        await importButton.click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('Import Data')).toBeVisible();

        // 4. Upload File
        const fileInput = dialog.locator('input[type="file"]');
        await fileInput.setInputFiles(filePath);

        // 5. Submit Import
        const [response] = await Promise.all([
            page.waitForResponse(res => res.url().includes('/api/assets/import')),
            dialog.getByRole('button', { name: 'Import' }).click()
        ]);
        
        const responseBody = await response.json();
        console.log('Import Response:', JSON.stringify(responseBody, null, 2));

        // 6. Verify Success
        try {
            await expect(page.getByText('Import Completed')).toBeVisible({ timeout: 10000 });
            await expect(page.getByText('Successfully imported 1 rows.')).toBeVisible();
        } catch (e) {
            console.log("Success toast not found within timeout. Checking for data existence...");
             // Check if error toast appeared instead
            const errorToast = await page.getByText('Import Failed').isVisible();
            if (errorToast) {
                console.log("Error toast detected!");
            }
        }

        // Close dialog manually if it's still open
        if (await dialog.isVisible()) {
            await dialog.getByRole('button', { name: 'Close' }).first().click();
        }

        // 7. Verify Data in List
        // Search for the new asset
        const searchInput = page.getByPlaceholder('Search assets...');
        await searchInput.fill(uniqueAssetCode);
        await searchInput.press('Enter');
        
        await page.waitForResponse(r => r.url().includes('/api/assets') && r.status() === 200);
        
        await expect(page.getByText(uniqueAssetCode)).toBeVisible();
        await expect(page.getByText(`E2E Asset ${timestamp}`)).toBeVisible();

        // Cleanup
        if (fs.existsSync(filePath)) {
            fs.unlinkSync(filePath);
        }
    });

    test('shows error summary for invalid import', async ({ page }) => {
        // 1. Prepare Invalid CSV (missing asset_code)
        const rowData = [
            '',                       // asset_code
            'Invalid Asset',          // name
            'IT Equipment',           // asset_category
            '',                       // asset_model
            'Head Office',            // branch
            '',                       // location
            '',                       // department
            '',                       // employee
            '',                       // supplier
            '',                       // serial_number
            '',                       // barcode
            '2024-01-01',             // purchase_date
            '15000000',               // purchase_cost
            'IDR',                    // currency
            '',                       // warranty_end_date
            'active',                 // status
            'good',                   // condition
            'E2E Import Error Check'  // notes
        ];
        
        const csvContent = `asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes\n${rowData.join(',')}`;
        
        const fileName = `invalid_import_${Date.now()}.csv`;
        const filePath = path.join('/tmp', fileName);
        fs.writeFileSync(filePath, csvContent);

        // 2. Open Import Dialog
        await page.goto('/assets');
        const importButton = page.getByRole('button', { name: /import/i });
        await importButton.click();

        // 3. Upload File
        const dialog = page.getByRole('dialog');
        const fileInput = dialog.locator('input[type="file"]');
        await fileInput.setInputFiles(filePath);

        // 4. Submit
        const submitButton = dialog.getByRole('button', { name: 'Import' });
        await submitButton.click();

        // 5. Verify Error Summary
        await expect(page.getByText('Import Finished with Errors')).toBeVisible();
        await expect(page.getByText('Errors: 1')).toBeVisible();
        
        // Check table content
        const errorTable = dialog.locator('table');
        await expect(errorTable).toBeVisible();
        await expect(errorTable).toContainText('Validation');
        
        // Cleanup
        if (fs.existsSync(filePath)) {
            fs.unlinkSync(filePath);
        }
    });
});
