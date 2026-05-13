import { Page } from '@playwright/test';
import { searchAndWaitForApi } from '../helpers';

export async function searchReportConfiguration(
    page: Page,
    query: string,
): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/Search/i),
        query,
        '/api/report-configurations',
    );
}
