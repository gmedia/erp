'use client';

import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

interface UseExportOptions {
    endpoint?: string;
    filename?: string;
}

export function useExport({
    endpoint = '/api/export',
    filename,
}: UseExportOptions = {}) {
    const [exporting, setExporting] = useState(false);

    const exportData = async (
        filters: Record<string, string | undefined> = {},
    ) => {
        if (!endpoint) return;

        setExporting(true);
        try {
            const cleanFilters = Object.fromEntries(
                Object.entries(filters).filter(
                    ([, v]) => v !== null && v !== '',
                ),
            );

            const response = await axios.post(endpoint, cleanFilters, {
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
            });

            const a = document.createElement('a');
            a.href = response.data.url;
            a.download = response.data.filename || filename || 'export.xlsx';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            toast.success('Export completed successfully');
        } catch (error) {
            console.error('Export failed:', error);
            toast.error('Failed to export data. Please try again.');
        } finally {
            setExporting(false);
        }
    };

    return {
        exporting,
        exportData,
    };
}
