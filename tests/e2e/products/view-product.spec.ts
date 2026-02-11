import { test, expect } from '@playwright/test';
import { createProduct, searchProduct, login } from '../helpers';

test.describe('Product Management - View', () => {
  test('should view product details', async ({ page }) => {
    const productCode = await createProduct(page, {
        name: 'Product to View',
        category_id: 'Electronics',
        unit_id: 'Piece',
        cost: '100',
        selling_price: '200',
    });
    
    await searchProduct(page, productCode);

    const row = page.locator(`tr:has-text("${productCode}")`);
    await expect(row).toBeVisible();

    // Open Actions menu
    const actionsButton = row.getByRole('button', { name: /Actions/i });
    await actionsButton.click();

    // Click View
    const viewButton = page.getByRole('menuitem', { name: /View/i });
    await viewButton.click();

    // Verify Dialog or Page
    // Assuming View opens a dialog or redirects to a view page.
    // Adjust selector based on actual implementation. 
    // If it's a dialog:
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText(productCode);
    await expect(dialog).toContainText('Product to View');
  });
});
