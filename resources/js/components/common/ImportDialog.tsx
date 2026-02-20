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
import { toast } from 'sonner';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, CheckCircle, Download, FileSpreadsheet, Loader2, Upload, X } from 'lucide-react';
import { useState } from 'react';

interface ImportDialogProps {
  title?: string;
  trigger?: React.ReactNode;
  importRoute: string;
  templateHeaders: string[];
  sampleData?: any[];
  onSuccess?: () => void;
}

export default function ImportDialog({
  title = 'Import Data',
  trigger,
  importRoute,
  templateHeaders,
  sampleData = [],
  onSuccess,
}: ImportDialogProps) {
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
    const rows = sampleData.length > 0 
      ? sampleData.map(row => templateHeaders.map(header => row[header] || '').join(','))
      : [templateHeaders.map(() => '').join(',')];
    
    const csvContent = "data:text/csv;charset=utf-8," + [headerRow, ...rows].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "import_template.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      setFile(e.target.files[0]);
      setResult(null); // Reset result when new file selected
    }
  };

  const handleImport = () => {
    if (!file) return;

    setLoading(true);
    const formData = new FormData();
    formData.append('file', file);

    router.post(importRoute, formData, {
      onSuccess: (page) => {
        setLoading(false);
        // The controller returns JSON, but Inertia handles it. 
        // We might need to handle the response data differently if it's a pure JSON response vs Inertia visit.
        // If the controller returns response()->json($summary), Inertia might treat it as a visit or we need to use axios.
        // Let's use axios for this specific action to get the JSON response easily without page reload logic,
        // OR better, checking how existing exports work. Existing exports rely on direct download.
        // For import, we want to stay on the page and show summary.
        // Let's use a standard fetch/axios approach for the import to handle the JSON response directly.
      },
      onError: (errors) => {
        setLoading(false);
        toast.error("Import Failed", {
          description: "There was an error uploading the file.",
        });
      },
      preserveState: true,
      preserveScroll: true,
    });
  };

  // Re-implementing handleImport to use axios for JSON response handling
  const handleImportAxios = async () => {
    if (!file) return;

    setLoading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await axios.post(importRoute, formData);
      setLoading(false);
      setResult(response.data);
      if (response.data.imported > 0) {
        toast.success("Import Completed", {
          description: `Successfully imported ${response.data.imported} rows.`,
        });
        if (onSuccess) onSuccess();
        // optionally refresh the page data
        router.reload();
      } else if (response.data.errors.length > 0) {
        toast.error("Import Finished with Errors", {
          description: "Check the error list below.",
        });
      } else {
         toast.success("Import Completed", {
          description: "No data was imported.",
        });
      }
    } catch (error: any) {
      setLoading(false);
      // Handle validation errors from Laravel (422)
      if (error.response && error.response.status === 422) {
         toast.error("Validation Error", {
          description: error.response.data.message || "Invalid file.",
        });
      } else {
        toast.error("Import Failed", {
          description: "An unexpected error occurred.",
        });
      }
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {trigger || (
          <Button variant="outline">
            <Upload className="mr-2 h-4 w-4" />
            Import
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md md:max-w-lg">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>
            Upload an Excel or CSV file to import data.
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-4 py-4">
          <div className="flex items-center justify-between">
            <Label>Template</Label>
            <Button variant="link" size="sm" onClick={downloadTemplate} className="h-auto p-0 text-primary">
              <Download className="mr-2 h-3.5 w-3.5" />
              Download Template
            </Button>
          </div>

          <div className="grid w-full max-w-sm items-center gap-1.5">
            <Label htmlFor="file">File</Label>
            <Input id="file" type="file" accept=".csv, .xlsx, .xls" onChange={handleFileChange} />
          </div>

          {result && (
            <div className="mt-4 space-y-3 rounded-md border p-3 text-sm">
              <div className="flex items-center gap-4 font-medium">
                <div className="flex items-center text-green-600">
                  <CheckCircle className="mr-1.5 h-4 w-4" />
                  Imported: {result.imported}
                </div>
                <div className="flex items-center text-yellow-600">
                  <AlertCircle className="mr-1.5 h-4 w-4" />
                  Skipped: {result.skipped}
                </div>
                <div className="flex items-center text-red-600">
                  <X className="mr-1.5 h-4 w-4" />
                  Errors: {result.errors.length}
                </div>
              </div>

              {result.errors.length > 0 && (
                <div className="max-h-40 overflow-y-auto rounded bg-slate-50 p-2 text-xs">
                  <table className="w-full text-left">
                    <thead>
                      <tr>
                        <th className="pb-1 text-slate-500">Row</th>
                        <th className="pb-1 text-slate-500">Field</th>
                        <th className="pb-1 text-slate-500">Message</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.errors.map((err, idx) => (
                        <tr key={idx} className="border-t border-slate-100">
                          <td className="py-1 align-top font-mono text-slate-500">{err.row}</td>
                          <td className="py-1 align-top font-medium text-slate-700">{err.field}</td>
                          <td className="py-1 align-top text-red-600">{err.message}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}
        </div>

        <DialogFooter className="sm:justify-end">
          <Button type="button" variant="secondary" onClick={() => setOpen(false)}>
            Close
          </Button>
          <Button 
            type="button" 
            onClick={handleImportAxios} 
            disabled={!file || loading}
          >
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Import
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
