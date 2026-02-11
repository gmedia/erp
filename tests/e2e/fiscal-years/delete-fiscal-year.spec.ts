import { test, expect } from '@playwright/test';
import { createFiscalYear, deleteFiscalYear } from '../helpers';

test('delete fiscal year end‑to‑end', async ({ page }) => {
  const name = await createFiscalYear(page);
  
  await deleteFiscalYear(page, name);
});
