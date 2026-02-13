import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('dashboard shows totals cards', async ({ page }) => {
  await login(page);

  await page.goto('/dashboard');

  const cards = [
    { label: 'Total Customer' },
    { label: 'Total Employee' },
    { label: 'Total Supplier' },
    { label: 'Total Asset' },
  ];

  for (const card of cards) {
    const container = page
      .locator('[data-slot="card"]')
      .filter({ hasText: card.label })
      .first();

    await expect(container).toBeVisible({ timeout: 10000 });

    const value = container.locator('[data-slot="card-content"]').first();
    await expect(value).toBeVisible();
    await expect(value).toHaveText(/[\d.,]+/);
  }
});

