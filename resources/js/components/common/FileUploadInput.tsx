import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/contexts/i18n-context';
import { Paperclip, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface FileUploadInputProps {
    id: string;
    name?: string;
    value: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    currentFileName?: string | null;
    buttonLabel?: string;
    placeholder?: string;
    disabled?: boolean;
    multiple?: boolean;
    showPreview?: boolean;
    hint?: string;
    capture?: boolean;
}

export function FileUploadInput({
    id,
    name,
    value,
    onChange,
    accept,
    currentFileName,
    buttonLabel,
    placeholder,
    disabled = false,
    multiple = false,
    showPreview = false,
    hint,
    capture = false,
}: Readonly<FileUploadInputProps>) {
    const { t } = useTranslation();
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
                    multiple={multiple}
                    capture={capture ? 'environment' : undefined}
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
                    {buttonLabel ?? t('file_upload.choose')}
                </Button>
                <span
                    className="min-w-0 flex-1 truncate text-sm text-muted-foreground"
                    data-testid={`${id}-file-name`}
                >
                    {value?.name ?? placeholder ?? t('file_upload.none')}
                </span>
                {value && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        disabled={disabled}
                        aria-label={t('file_upload.remove')}
                        onClick={() => {
                            if (inputRef.current) inputRef.current.value = '';
                            onChange(null);
                        }}
                    >
                        <X className="size-4" aria-hidden="true" />
                    </Button>
                )}
            </div>
            {hint && !value && (
                <p className="text-xs text-muted-foreground">{hint}</p>
            )}
            {showPreview && value && (
                <FilePreview file={value} onClear={() => onChange(null)} />
            )}
            {!value && currentFileName && (
                <p className="text-xs text-muted-foreground">
                    {t('file_upload.current', { name: currentFileName })}
                </p>
            )}
        </div>
    );
}

export function FilePreview({
    file,
    onClear,
}: Readonly<{ file: File; onClear?: () => void }>) {
    const { t } = useTranslation();
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file.type.startsWith('image/')) return;

        let active = true;
        const reader = new FileReader();
        reader.onload = () => {
            if (active && typeof reader.result === 'string') {
                setPreviewUrl(reader.result);
            }
        };
        reader.readAsDataURL(file);

        return () => {
            active = false;
        };
    }, [file]);

    return (
        <div className="flex items-center gap-3 rounded-md border bg-muted/30 p-2">
            {previewUrl ? (
                <img
                    src={previewUrl}
                    alt={t('file_upload.preview', { name: file.name })}
                    className="h-16 w-16 rounded object-cover"
                />
            ) : (
                <div className="h-16 w-16 rounded bg-muted" />
            )}
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{file.name}</p>
                <p className="text-xs text-muted-foreground">
                    {(file.size / 1024).toFixed(0)} KB
                </p>
            </div>
            {onClear && (
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    onClick={onClear}
                >
                    {t('file_upload.clear')}
                </Button>
            )}
        </div>
    );
}
