import { memo, useState } from 'react';

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
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from '@/lib/axios';
import { useQueryClient } from '@tanstack/react-query';
import rawAxios from 'axios';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    FileSpreadsheet,
    Loader2,
    Upload,
    X,
} from 'lucide-react';
import { toast } from 'sonner';

interface ImportBankStatementDialogProps {
    bankReconciliationId: number;
    trigger?: React.ReactNode;
    onSuccess?: () => void;
}

interface PreviewData {
    headers: string[];
    preview_rows: Record<string, string | number>[];
}

interface ImportResult {
    imported: number;
    skipped: number;
    errors: { row: number; field: string; message: string }[];
}

interface ColumnMapping {
    date: string;
    description: string;
    amount?: string;
    debit?: string;
    credit?: string;
    reference?: string;
}

type AmountMode = 'single' | 'separate';

export const ImportBankStatementDialog = memo<ImportBankStatementDialogProps>(
    function ImportBankStatementDialog({
        bankReconciliationId,
        trigger,
        onSuccess,
    }) {
        const queryClient = useQueryClient();
        const [open, setOpen] = useState(false);
        const [step, setStep] = useState<1 | 2 | 3>(1);
        const [file, setFile] = useState<File | null>(null);
        const [loading, setLoading] = useState(false);
        const [previewData, setPreviewData] = useState<PreviewData | null>(
            null,
        );
        const [amountMode, setAmountMode] = useState<AmountMode>('single');
        const [mapping, setMapping] = useState<ColumnMapping>({
            date: '',
            description: '',
            amount: '',
            debit: '',
            credit: '',
            reference: '',
        });
        const [result, setResult] = useState<ImportResult | null>(null);

        const resetDialog = () => {
            setStep(1);
            setFile(null);
            setPreviewData(null);
            setMapping({
                date: '',
                description: '',
                amount: '',
                debit: '',
                credit: '',
                reference: '',
            });
            setAmountMode('single');
            setResult(null);
        };

        const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.target.files && e.target.files.length > 0) {
                setFile(e.target.files[0]);
            }
        };

        const handleNextToMapping = async () => {
            if (!file) return;

            setLoading(true);
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await axios.post<PreviewData>(
                    '/api/bank-reconciliations/import-preview',
                    formData,
                );
                setPreviewData(response.data);
                setStep(2);
            } catch (error: unknown) {
                if (
                    rawAxios.isAxiosError(error) &&
                    error.response?.status === 422
                ) {
                    toast.error('Invalid File', {
                        description:
                            error.response.data.message ||
                            'Unable to read file.',
                    });
                } else {
                    toast.error('Upload Failed', {
                        description: 'An unexpected error occurred.',
                    });
                }
            } finally {
                setLoading(false);
            }
        };

        const handleImport = async () => {
            if (!file) return;

            const isValid =
                mapping.date &&
                mapping.description &&
                (amountMode === 'single'
                    ? mapping.amount
                    : mapping.debit && mapping.credit);

            if (!isValid) {
                toast.error('Incomplete Mapping', {
                    description: 'Please map all required columns.',
                });
                return;
            }

            setLoading(true);
            const formData = new FormData();
            formData.append('file', file);
            formData.append('mapping[date]', mapping.date);
            formData.append('mapping[description]', mapping.description);

            if (amountMode === 'single' && mapping.amount) {
                formData.append('mapping[amount]', mapping.amount);
            } else {
                if (mapping.debit) {
                    formData.append('mapping[debit]', mapping.debit);
                }
                if (mapping.credit) {
                    formData.append('mapping[credit]', mapping.credit);
                }
            }

            if (mapping.reference) {
                formData.append('mapping[reference]', mapping.reference);
            }

            try {
                const response = await axios.post<ImportResult>(
                    `/api/bank-reconciliations/${bankReconciliationId}/import-statement`,
                    formData,
                );
                setResult(response.data);
                setStep(3);

                if (response.data.imported > 0) {
                    toast.success('Import Completed', {
                        description: `Successfully imported ${response.data.imported} transactions.`,
                    });
                    await queryClient.invalidateQueries({
                        queryKey: ['bank-reconciliations'],
                    });
                    if (onSuccess) onSuccess();
                } else if (response.data.errors.length > 0) {
                    toast.error('Import Finished with Errors', {
                        description: 'Check the error list below.',
                    });
                }
            } catch (error: unknown) {
                if (
                    rawAxios.isAxiosError(error) &&
                    error.response?.status === 422
                ) {
                    toast.error('Validation Error', {
                        description:
                            error.response.data.message || 'Invalid data.',
                    });
                } else {
                    toast.error('Import Failed', {
                        description: 'An unexpected error occurred.',
                    });
                }
            } finally {
                setLoading(false);
            }
        };

        const handleClose = () => {
            setOpen(false);
            setTimeout(resetDialog, 300);
        };

        const renderStepContent = () => {
            if (step === 1) {
                return (
                    <div className="grid gap-4 py-4">
                        <div className="grid w-full items-center gap-2">
                            <Label htmlFor="statement-file">
                                Bank Statement File
                            </Label>
                            <Input
                                id="statement-file"
                                type="file"
                                accept=".csv, .xlsx, .xls"
                                onChange={handleFileChange}
                            />
                            <p className="text-xs text-muted-foreground">
                                Upload a CSV or Excel file containing bank
                                statement transactions.
                            </p>
                        </div>
                    </div>
                );
            }

            if (step === 2 && previewData) {
                const headers = previewData.headers;
                const previewRows = previewData.preview_rows.slice(0, 5);

                return (
                    <div className="grid gap-4 py-4">
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="date-column">
                                        Date Column{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={mapping.date}
                                        onValueChange={(value) =>
                                            setMapping((prev) => ({
                                                ...prev,
                                                date: value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="date-column">
                                            <SelectValue placeholder="Select column" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {headers.map((header) => (
                                                <SelectItem
                                                    key={header}
                                                    value={header}
                                                >
                                                    {header}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description-column">
                                        Description Column{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={mapping.description}
                                        onValueChange={(value) =>
                                            setMapping((prev) => ({
                                                ...prev,
                                                description: value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="description-column">
                                            <SelectValue placeholder="Select column" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {headers.map((header) => (
                                                <SelectItem
                                                    key={header}
                                                    value={header}
                                                >
                                                    {header}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>
                                    Amount Mode{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <div className="flex gap-4">
                                    <label className="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="amount-mode"
                                            value="single"
                                            checked={amountMode === 'single'}
                                            onChange={() =>
                                                setAmountMode('single')
                                            }
                                            className="size-4"
                                        />
                                        <span className="text-sm">
                                            Single Amount
                                        </span>
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="amount-mode"
                                            value="separate"
                                            checked={amountMode === 'separate'}
                                            onChange={() =>
                                                setAmountMode('separate')
                                            }
                                            className="size-4"
                                        />
                                        <span className="text-sm">
                                            Separate Debit/Credit
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {amountMode === 'single' ? (
                                <div className="space-y-2">
                                    <Label htmlFor="amount-column">
                                        Amount Column{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={mapping.amount}
                                        onValueChange={(value) =>
                                            setMapping((prev) => ({
                                                ...prev,
                                                amount: value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="amount-column">
                                            <SelectValue placeholder="Select column" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {headers.map((header) => (
                                                <SelectItem
                                                    key={header}
                                                    value={header}
                                                >
                                                    {header}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="debit-column">
                                            Debit Column{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            value={mapping.debit}
                                            onValueChange={(value) =>
                                                setMapping((prev) => ({
                                                    ...prev,
                                                    debit: value,
                                                }))
                                            }
                                        >
                                            <SelectTrigger id="debit-column">
                                                <SelectValue placeholder="Select column" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {headers.map((header) => (
                                                    <SelectItem
                                                        key={header}
                                                        value={header}
                                                    >
                                                        {header}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="credit-column">
                                            Credit Column{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            value={mapping.credit}
                                            onValueChange={(value) =>
                                                setMapping((prev) => ({
                                                    ...prev,
                                                    credit: value,
                                                }))
                                            }
                                        >
                                            <SelectTrigger id="credit-column">
                                                <SelectValue placeholder="Select column" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {headers.map((header) => (
                                                    <SelectItem
                                                        key={header}
                                                        value={header}
                                                    >
                                                        {header}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="reference-column">
                                    Reference Column{' '}
                                    <span className="text-xs text-muted-foreground">
                                        (Optional)
                                    </span>
                                </Label>
                                <Select
                                    value={mapping.reference}
                                    onValueChange={(value) =>
                                        setMapping((prev) => ({
                                            ...prev,
                                            reference: value,
                                        }))
                                    }
                                >
                                    <SelectTrigger id="reference-column">
                                        <SelectValue placeholder="Select column" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">None</SelectItem>
                                        {headers.map((header) => (
                                            <SelectItem
                                                key={header}
                                                value={header}
                                            >
                                                {header}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Preview (First 5 Rows)</Label>
                            <ScrollArea className="h-[200px] rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            {headers.map((header) => (
                                                <TableHead key={header}>
                                                    {header}
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {previewRows.map((row, idx) => (
                                            <TableRow key={idx}>
                                                {headers.map((header) => (
                                                    <TableCell key={header}>
                                                        {String(
                                                            row[header] ?? '',
                                                        )}
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </ScrollArea>
                        </div>
                    </div>
                );
            }

            if (step === 3 && result) {
                return (
                    <div className="space-y-4 py-4">
                        <div className="flex items-center justify-center gap-4 rounded-md border p-6">
                            <div className="flex flex-col items-center gap-2 text-center">
                                <CheckCircle2 className="size-12 text-green-600" />
                                <p className="font-medium">Import Completed</p>
                            </div>
                        </div>

                        <div className="space-y-3 rounded-md border p-4 text-sm">
                            <div className="flex items-center gap-4 font-medium">
                                <div className="flex items-center text-green-600">
                                    <CheckCircle2 className="mr-1.5 size-4" />
                                    Imported: {result.imported}
                                </div>
                                <div className="flex items-center text-yellow-600">
                                    <AlertCircle className="mr-1.5 size-4" />
                                    Skipped: {result.skipped}
                                </div>
                                <div className="flex items-center text-red-600">
                                    <X className="mr-1.5 size-4" />
                                    Errors: {result.errors.length}
                                </div>
                            </div>

                            {result.errors.length > 0 && (
                                <ScrollArea className="max-h-40 rounded bg-slate-50">
                                    <div className="p-2 pr-4 text-xs">
                                        <table className="w-full text-left">
                                            <thead>
                                                <tr>
                                                    <th className="pb-1 text-slate-500">
                                                        Row
                                                    </th>
                                                    <th className="pb-1 text-slate-500">
                                                        Field
                                                    </th>
                                                    <th className="pb-1 text-slate-500">
                                                        Message
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {result.errors.map((err) => (
                                                    <tr
                                                        key={`${err.row}-${err.field}-${err.message}`}
                                                        className="border-t border-slate-100"
                                                    >
                                                        <td className="py-1 align-top font-mono text-slate-500">
                                                            {err.row}
                                                        </td>
                                                        <td className="py-1 align-top font-medium text-slate-700">
                                                            {err.field}
                                                        </td>
                                                        <td className="py-1 align-top text-red-600">
                                                            {err.message}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </ScrollArea>
                            )}
                        </div>
                    </div>
                );
            }

            return null;
        };

        return (
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild>
                    {trigger || (
                        <Button variant="outline" size="sm">
                            <FileSpreadsheet className="mr-2 size-4" />
                            Import Statement
                        </Button>
                    )}
                </DialogTrigger>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            Import Bank Statement
                            {step === 2 && ' - Map Columns'}
                            {step === 3 && ' - Results'}
                        </DialogTitle>
                        <DialogDescription>
                            {step === 1 &&
                                'Upload a bank statement file to import transactions.'}
                            {step === 2 &&
                                'Map the columns from your file to the required fields.'}
                            {step === 3 && 'Review the import results below.'}
                        </DialogDescription>
                    </DialogHeader>

                    {renderStepContent()}

                    <DialogFooter className="sm:justify-between">
                        <div>
                            {step === 2 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => setStep(1)}
                                    disabled={loading}
                                >
                                    <ArrowLeft className="mr-2 size-4" />
                                    Back
                                </Button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={handleClose}
                            >
                                {step === 3 ? 'Close' : 'Cancel'}
                            </Button>
                            {step === 1 && (
                                <Button
                                    type="button"
                                    onClick={handleNextToMapping}
                                    disabled={!file || loading}
                                >
                                    {loading ? (
                                        <>
                                            <Loader2 className="mr-2 size-4 animate-spin" />
                                            Processing...
                                        </>
                                    ) : (
                                        <>
                                            Next
                                            <ArrowRight className="ml-2 size-4" />
                                        </>
                                    )}
                                </Button>
                            )}
                            {step === 2 && (
                                <Button
                                    type="button"
                                    onClick={handleImport}
                                    disabled={loading}
                                >
                                    {loading ? (
                                        <>
                                            <Loader2 className="mr-2 size-4 animate-spin" />
                                            Importing...
                                        </>
                                    ) : (
                                        <>
                                            <Upload className="mr-2 size-4" />
                                            Import
                                        </>
                                    )}
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    },
);
