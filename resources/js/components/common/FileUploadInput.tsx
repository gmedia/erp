import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Paperclip, X } from 'lucide-react';
import { useRef } from 'react';

interface FileUploadInputProps {
    id: string;
    name?: string;
    value: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    /** Nama berkas yang sudah tersimpan di server (mis. dokumen existing). */
    currentFileName?: string | null;
    buttonLabel?: string;
    placeholder?: string;
    disabled?: boolean;
}

export function FileUploadInput({
    id,
    name,
    value,
    onChange,
    accept,
    currentFileName,
    buttonLabel = 'Pilih Berkas',
    placeholder = 'Belum ada berkas dipilih',
    disabled = false,
}: Readonly<FileUploadInputProps>) {
    const inputRef = useRef<HTMLInputElement>(null);

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
                <Input
                    ref={inputRef}
                    id={id}
                    name={name}
                    type="file"
                    accept={accept}
                    disabled={disabled}
                    className="hidden"
                    onChange={(event) =>
                        onChange(event.target.files?.[0] ?? null)
                    }
                />
                <Button
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    onClick={() => inputRef.current?.click()}
                >
                    <Paperclip className="size-4" aria-hidden="true" />
                    {buttonLabel}
                </Button>
                <span
                    className="min-w-0 flex-1 truncate text-sm text-muted-foreground"
                    data-testid={`${id}-file-name`}
                >
                    {value?.name ?? placeholder}
                </span>
                {value && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        disabled={disabled}
                        aria-label="Hapus berkas"
                        onClick={() => {
                            if (inputRef.current) inputRef.current.value = '';
                            onChange(null);
                        }}
                    >
                        <X className="size-4" aria-hidden="true" />
                    </Button>
                )}
            </div>
            {!value && currentFileName && (
                <p className="text-xs text-muted-foreground">
                    Saat ini: {currentFileName}
                </p>
            )}
        </div>
    );
}
