import { test, expect } from '@playwright/test';
import { createAccountMapping, searchAccountMappings, findAccountMappingRow } from './helpers';

test.describe('Account Mapping Search', () => {
    test('should search account mappings', async ({ page }) => {
        const { sourceCode, targetCode } = await createAccountMapping(page);

        await searchAccountMappings(page, sourceCode);

        const row = findAccountMappingRow(page, sourceCode, targetCode);
        await expect(row).toBeVisible();
    });
});
