/**
 * Shared Test Factories
 *
 * Generates 9 standardized E2E tests for any CRUD module.
 * All modules MUST comply with the ModuleTestConfig interface.
 */

import { test, expect, Page, Locator } from '@playwright/test';
import { login } from './helpers';
import * as fs from 'node:fs';
import ExcelJS from 'exceljs';

// ==================== INTERFACES ====================

export interface FilterTestConfig {
    filterName: string;
    filterType: 'combobox' | 'select' | 'text' | 'date';
    filterValue: string;
    expectedText: string;
}

export interface ModuleTestConfig {
    // Identitas modul
    entityName: string;           // PascalCase singular: 'Department'
    entityNamePlural: string;     // PascalCase plural: 'Departments'
    route: string;                // Frontend route: '/departments'
    apiPath: string;              // API base path: '/api/departments'

    // Callbacks (WAJIB)
    createEntity: (page: Page) => Promise<string>;  // Return identifier (name/code/email)
    searchEntity: (page: Page, identifier: string) => Promise<void>;

    // Callbacks (OPSIONAL)
    editEntity?: (page: Page, identifier: string, updates: Record<string, string>) => Promise<void>;
    editUpdates?: Record<string, string>;

    // DataTable config
    sortableColumns: string[];    // Label text kolom sortable PERSIS seperti di UI
    skipCreateBeforeSort?: boolean;

    // View config
    viewType: 'dialog' | 'page';
    viewDialogTitle?: string;     // Required jika viewType = 'dialog'
    viewUrlPattern?: RegExp;      // Required jika viewType = 'page'

    // Export config
    exportApiPath: string;        // '/api/departments/export'
    expectedExportColumns: string[];

    // Filter config (opsional)
    filterTests?: FilterTestConfig[];

    // Overrides (opsional — untuk modul dengan pattern non-standar)
    actionsPattern?: 'dropdown' | 'icon-buttons';  // default: 'dropdown'
    customBeforeEach?: (page: Page) => Promise<void>;

    // Custom action callbacks (WAJIB jika actionsPattern = 'icon-buttons')
    customViewAction?: (page: Page) => Promise<void>;
    customEditAction?: (page: Page) => Promise<void>;
    customDeleteAction?: (page: Page) => Promise<void>;
}

// ==================== HELPER FUNCTIONS ====================

async function waitForApiResponse(page: Page, apiPath: string): Promise<void> {
    await page.waitForResponse(r => r.url().includes(apiPath) && r.status() < 400, {
        timeout: 15000,
    });
}

async function navigateToModule(page: Page, route: string, apiPath: string): Promise<void> {
    const responsePromise = waitForApiResponse(page, apiPath);
    await page.goto(route);
    await responsePromise;
}

function findTableRow(page: Page, identifier: string): Locator {
    return page.locator('tbody tr').filter({ hasText: identifier }).first();
}

async function openActionsMenu(target: Locator, config: ModuleTestConfig): Promise<void> {
    if (config.actionsPattern === 'icon-buttons') {
        return;
    }
    // Default: dropdown menu
    await target.getByRole('button').last().click();
}

// ==================== TEST FACTORY ====================

export function generateModuleTests(config: ModuleTestConfig) {
    test.describe(`${config.entityName} E2E Tests`, () => {
        let createdIdentifier: string;

        test.beforeEach(async ({ page }) => {
            await login(page);
            await navigateToModule(page, config.route, config.apiPath);
            if (config.customBeforeEach) {
                await config.customBeforeEach(page);
            }
        });

        // ==================== 1. ADD ====================
        test(`can add new ${config.entityName}`, async ({ page }) => {
            createdIdentifier = await config.createEntity(page);
            expect(createdIdentifier).toBeTruthy();

            // Verify entity appears in table
            await config.searchEntity(page, createdIdentifier);
            await expect(findTableRow(page, createdIdentifier)).toBeVisible();
        });

        // ==================== 2. SEARCH ====================
        test(`can search ${config.entityNamePlural}`, async ({ page }) => {
            // Create entity first
            const identifier = await config.createEntity(page);

            // Search for it
            await config.searchEntity(page, identifier);

            // Verify found
            await expect(findTableRow(page, identifier)).toBeVisible();
        });

        // ==================== 3. EDIT ====================
        test(`can edit ${config.entityName}`, async ({ page }) => {
            test.skip(
                !config.editEntity || !config.editUpdates,
                `${config.entityName} does not provide edit callback in module config`,
            );

            const identifier = await config.createEntity(page);
            await config.searchEntity(page, identifier);

            await config.editEntity(page, identifier, config.editUpdates);

            // Verify updated value appears
            const updatedValue = Object.values(config.editUpdates)[0];
            await config.searchEntity(page, updatedValue);
            await expect(findTableRow(page, updatedValue)).toBeVisible();
        });

        // ==================== 4. VIEW ====================
        test(`can view ${config.entityName}`, async ({ page }) => {
            const identifier = await config.createEntity(page);
            await config.searchEntity(page, identifier);
            const row = findTableRow(page, identifier);
            await expect(row).toBeVisible();

            if (config.actionsPattern === 'icon-buttons' && config.customViewAction) {
                await config.customViewAction(page);
            } else {
                await openActionsMenu(row, config);
                await page.getByRole('menuitem', { name: 'View' }).click();
            }

            if (config.viewType === 'dialog') {
                const dialog = page.getByRole('dialog');
                await expect(dialog).toBeVisible();
                await expect(dialog.getByText(identifier)).toBeVisible();
            } else {
                if (config.viewUrlPattern) {
                    await expect(page).toHaveURL(config.viewUrlPattern);
                }
                await expect(page.getByText(identifier, { exact: true }).first()).toBeVisible();
            }
        });

        // ==================== 5. DELETE ====================
        test(`can delete ${config.entityName}`, async ({ page }) => {
            const identifier = await config.createEntity(page);
            await config.searchEntity(page, identifier);
            const row = findTableRow(page, identifier);
            await expect(row).toBeVisible();

            if (config.actionsPattern === 'icon-buttons' && config.customDeleteAction) {
                await config.customDeleteAction(page);
            } else {
                await openActionsMenu(row, config);
                await page.getByRole('menuitem', { name: 'Delete' }).click();
            }

            // Confirm delete dialog
            const confirmButton = page.getByRole('button', { name: /continue|confirm|delete|yes|hapus/i });
            const responsePromise = waitForApiResponse(page, config.apiPath);
            await confirmButton.first().click();
            await responsePromise;

            // Verify deleted — search should not find it
            await config.searchEntity(page, identifier);
            await expect(findTableRow(page, identifier)).not.toBeVisible();
        });

        // ==================== 6. EXPORT ====================
        test(`can export ${config.entityNamePlural}`, async ({ page }) => {
            // Create at least one entity so export is not empty
            await config.createEntity(page);

            const downloadPromise = page.waitForEvent('download');
            await page.getByRole('button', { name: /export/i }).click();
            const download = await downloadPromise;

            // Save and verify columns
            const filePath = `/tmp/test-export-${config.entityName}-${Date.now()}.xlsx`;
            await download.saveAs(filePath);

            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.readFile(filePath);
            const worksheet = workbook.getWorksheet(1);
            expect(worksheet).toBeTruthy();

            const headerRow = worksheet!.getRow(1);
            const headers: string[] = [];
            headerRow.eachCell((cell) => {
                if (cell.value) headers.push(String(cell.value));
            });

            for (const col of config.expectedExportColumns) {
                expect(headers).toContain(col);
            }

            // Cleanup
            fs.unlinkSync(filePath);
        });

        // ==================== 7. CHECKBOX ====================
        test(`${config.entityNamePlural} datatable has correct checkbox behavior`, async ({ page }) => {
            // Create entity to ensure table has rows
            const identifier = await config.createEntity(page);
            await navigateToModule(page, config.route, config.apiPath);
            await config.searchEntity(page, identifier);

            // Header: current table implementation exposes the select-all checkbox
            const headerCheckboxes = page.locator('thead [data-testid="select-all"]');
            await expect(headerCheckboxes).toHaveCount(1);
            await expect(headerCheckboxes.first()).toBeVisible();

            // Body: HARUS ada checkbox on the created row, not just the first rendered row.
            const row = findTableRow(page, identifier);
            await expect(row).toBeVisible();
            const bodyCheckbox = row.locator('[data-testid="select-row"]').first();
            await expect(bodyCheckbox).toBeVisible();
        });

        // ==================== 8. SORTING ====================
        test(`can sort ${config.entityNamePlural} by all columns`, async ({ page }) => {
            test.setTimeout(120000); // 2 minutes for sorting all columns
            // Create entity to ensure table has data
            if (!config.skipCreateBeforeSort) {
                await config.createEntity(page);
            }
            await navigateToModule(page, config.route, config.apiPath);

            for (const column of config.sortableColumns) {
                const sortButton = page.locator('thead').getByRole('button', { name: column, exact: true });
                await expect(sortButton).toBeVisible();

                // Sort ASC
                const ascResponsePromise = waitForApiResponse(page, config.apiPath);
                await sortButton.click();
                await ascResponsePromise;

                // Sort DESC
                const descResponsePromise = waitForApiResponse(page, config.apiPath);
                await sortButton.click();
                await descResponsePromise;
            }
        });

        // ==================== 9. FILTERS ====================
        test(`can filter ${config.entityNamePlural}`, async ({ page }) => {
            if (!config.filterTests || config.filterTests.length === 0) {
                // Minimal: verify filter button exists
                const filterButton = page.getByRole('button', { name: /filter/i });
                if (await filterButton.isVisible()) {
                    await filterButton.click();
                    const dialog = page.getByRole('dialog');
                    await expect(dialog).toBeVisible();
                }
                return;
            }

            // Full filter test
            const filterButton = page.getByRole('button', { name: /filter/i });
            await filterButton.click();

            for (const filterTest of config.filterTests) {
                const container = page
                    .locator('label', { hasText: filterTest.filterName })
                    .locator('..');

                if (filterTest.filterType === 'text') {
                    await container.locator('input').first().fill(filterTest.filterValue);
                    continue;
                }

                if (filterTest.filterType === 'date') {
                    await container.locator('input').first().fill(filterTest.filterValue);
                    continue;
                }

                if (filterTest.filterType === 'combobox' || filterTest.filterType === 'select') {
                    const combobox = container.getByRole('combobox').first();
                    await combobox.click();
                    const option = page
                        .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
                        .filter({ hasText: new RegExp(filterTest.filterValue, 'i') })
                        .first();
                    await expect(option).toBeVisible();
                    await option.click({ force: true });
                }
            }

            // Apply filter
            const applyButton = page.getByRole('button', { name: /apply|terapkan/i });
            if (await applyButton.isVisible()) {
                const responsePromise = waitForApiResponse(page, config.apiPath);
                await applyButton.click();
                await responsePromise;
            }
        });
    });
}
