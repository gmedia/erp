import { test, expect, Page } from '@playwright/test';
import { login } from './helpers';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Configuration for Simple CRUD E2E tests.
 */
export interface SimpleCrudTestConfig {
  /** Entity name in singular form (e.g., 'department') */
  entityName: string;
  /** Entity name in plural form (e.g., 'departments') */
  entityNamePlural: string;
  /** Route path (e.g., '/departments') */
  route: string;
  /** Search placeholder text */
  searchPlaceholder: string;
  /** Function to create entity, returns identifier */
  createEntity: (page: Page, overrides?: Record<string, string>) => Promise<string>;
  /** Function to search entity */
  searchEntity: (page: Page, identifier: string) => Promise<void>;
  /** Function to edit entity */
  editEntity: (page: Page, identifier: string, updates: { name: string }) => Promise<void>;
}

/**
 * Run all standard Simple CRUD E2E tests for a given entity configuration.
 */
export function runSimpleCrudE2ETests(config: SimpleCrudTestConfig) {
  test.describe(`${config.entityName} CRUD E2E Tests`, () => {
    
    test(`add new ${config.entityName}`, async ({ page }) => {
      const name = await config.createEntity(page);
      await config.searchEntity(page, name);
      const row = page.locator(`text=${name}`);
      await expect(row).toBeVisible();
    });

    test(`edit ${config.entityName}`, async ({ page }) => {
      const name = await config.createEntity(page);
      await config.searchEntity(page, name);
      const updatedName = name + ' Updated';
      await config.editEntity(page, name, { name: updatedName });
      await expect(page.locator('text=' + updatedName)).toBeVisible();
    });

    test(`delete ${config.entityName}`, async ({ page }) => {
      const name = await config.createEntity(page);
      await config.searchEntity(page, name);
      
      const row = page.locator('tr', { hasText: name }).first();
      await expect(row).toBeVisible();

      const actionsButton = row.getByRole('button', { name: /Actions/i });
      await actionsButton.click();

      const deleteMenuItem = page.getByRole('menuitem', { name: /Delete/i });
      await expect(deleteMenuItem).toBeVisible();
      await deleteMenuItem.click();

      const confirmButton = page.getByRole('button', { name: /Delete|Confirm/i });
      await expect(confirmButton).toBeVisible();
      await confirmButton.click();

      await page.waitForLoadState('networkidle');
      await expect(page.locator(`text=${name}`)).not.toBeVisible();
    });

    test(`export ${config.entityNamePlural} to Excel`, async ({ page }) => {
      await login(page);
      await config.createEntity(page);
      await page.goto(config.route);

      const exportBtn = page.getByRole('button', { name: /Export/i });
      await expect(exportBtn).toBeVisible();

      const [download] = await Promise.all([
        page.waitForEvent('download'),
        exportBtn.click(),
      ]);

      const downloadsDir = path.resolve('e2e/test-results', 'downloads');
      if (!fs.existsSync(downloadsDir)) {
        fs.mkdirSync(downloadsDir, { recursive: true });
      }
      const destPath = path.join(downloadsDir, download.suggestedFilename());
      await download.saveAs(destPath);

      expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
      expect(fs.existsSync(destPath)).toBeTruthy();

      const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
      expect(header).toBe('PK');
    });

    test(`filter ${config.entityNamePlural} by search`, async ({ page }) => {
      await login(page);
      
      await config.createEntity(page);
      await config.createEntity(page);
      const target = await config.createEntity(page);

      await page.goto(config.route);

      await page.fill(`input[placeholder="${config.searchPlaceholder}"]`, target);
      await page.press(`input[placeholder="${config.searchPlaceholder}"]`, 'Enter');
      await page.waitForLoadState('networkidle');

      await expect(page.locator(`text=${target}`)).toBeVisible();
    });
  });
}
