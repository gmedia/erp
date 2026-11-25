import { ImportResultSummary } from '@/components/common/ImportResultSummary';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/contexts/i18n-context';
import { usePermissions } from '@/hooks/usePermissions';
import axios from '@/lib/axios';
import rawAxios from 'axios';
import { Download, Loader2, Upload } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface ImportDialogProps {
    title?: string;
    trigger?: React.ReactNode;
    importRoute: string;
    templateHeaders: string[];
    sampleData?: Record<string, unknown>[];
    onSuccess?: () => void;
}

/**
 * Tombol import hanya tampil bila pengguna punya `permission` (sama dengan middleware route import).
 */
export default function ImportDialog({
    permission,
    ...props
}: Readonly<ImportDialogProps & { permission?: string }>) {
    const { can } = usePermissions();

    if (permission && !can(permission)) return null;

    return <ImportDialogContent {...props} />;
}

function ImportDialogContent({
    title,
    trigger,
    importRoute,
    templateHeaders,
    sampleData = [],
    onSuccess,
}: Readonly<ImportDialogProps>) {
    const { t } = useTranslation();
    const dialogTitle = title ?? t('shell.import_title');
    const [open, setOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<{
        imported: number;
        skipped: number;
        errors: { row: number; field: string; message: string }[];
    } | null>(null);

    const downloadTemplate = () => {
        // Create CSV content
        const headerRow = templateHeaders.join(',');
        // Add an empty row or sample data
        const rows =
            sampleData.length > 0
                ? sampleData.map((row) =>
                      templateHeaders
                          .map((header) => {
                              const value = row[header];
                              if (value == null) {
                                  return '';
                              }

                              if (typeof value === 'string') {
                                  return value;
                              }

                              if (
                                  typeof value === 'number' ||
                                  typeof value === 'boolean' ||
                                  typeof value === 'bigint'
                              ) {
                                  return value.toString();
                              }

                              if (value instanceof Date) {
                                  return value.toISOString();
                              }

                              if (typeof value === 'object') {
                                  return JSON.stringify(value);
                              }

                              return '';
                          })
                          .join(','),
                  )
                : [templateHeaders.map(() => '').join(',')];

        const csvContent =
            'data:text/csv;charset=utf-8,' + [headerRow, ...rows].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'import_template.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files.length > 0) {
            setFile(e.target.files[0] ?? null);
            setResult(null); // Reset result when new file selected
        }
    };

    const handleImport = async () => {
        if (!file) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post(importRoute, formData);
            setLoading(false);
            setResult(response.data);
            if (response.data.imported > 0) {
                toast.success(t('shell.import_completed'), {
                    description: t('shell.import_success_rows', {
                        count: String(response.data.imported),
                    }),
                });
                if (onSuccess) onSuccess();
                globalThis.location.reload();
            } else if (response.data.errors.length > 0) {
                toast.error(t('shell.import_finished_with_errors'), {
                    description: t('shell.import_check_errors'),
                });
            } else {
                toast.success(t('shell.import_completed'), {
                    description: t('shell.import_no_data'),
                });
            }
        } catch (error: unknown) {
            setLoading(false);
            if (
                rawAxios.isAxiosError(error) &&
                error.response?.status === 422
            ) {
                toast.error(t('shell.import_validation_error'), {
                    description:
                        error.response.data.message ||
                        t('shell.import_invalid_file'),
                });
            } else {
                toast.error(t('shell.import_failed'), {
                    description: t('shell.import_unexpected_error'),
                });
            }
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger || (
                    <Button variant="outline" aria-label={t('shell.import')}>
                        <Upload className="h-4 w-4 sm:mr-2" />
                        <span className="hidden sm:inline">
                            {t('shell.import')}
                        </span>
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md md:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{dialogTitle}</DialogTitle>
                    <DialogDescription>
                        {t('shell.import_description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                    <div className="flex items-center justify-between">
                        <Label>{t('shell.import_template')}</Label>
                        <Button
                            variant="link"
                            size="sm"
                            onClick={downloadTemplate}
                            className="h-auto p-0 text-primary"
                        >
                            <Download className="mr-2 h-3.5 w-3.5" />
                            {t('shell.import_download_template')}
                        </Button>
                    </div>

                    <div className="grid w-full max-w-sm items-center gap-1.5">
                        <Label htmlFor="file">{t('shell.import_file')}</Label>
                        <Input
                            id="file"
                            type="file"
                            accept=".csv, .xlsx, .xls"
                            onChange={handleFileChange}
                        />
                    </div>

                    {result && (
                        <ImportResultSummary result={result} className="mt-4" />
                    )}
                </div>

                <DialogFooter className="sm:justify-end">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setOpen(false)}
                    >
                        {t('shell.import_close')}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleImport}
                        disabled={!file || loading}
                    >
                        {loading && (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        )}
                        {t('shell.import')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
