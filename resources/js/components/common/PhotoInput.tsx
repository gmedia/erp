import { Button } from '@/components/ui/button';
import { takePhoto } from '@/lib/camera';
import { Capacitor } from '@capacitor/core';
import { useEffect, useRef, useState } from 'react';

interface PhotoInputProps {
    id: string;
    value: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    buttonLabel?: string;
    hint?: string;
    capture?: boolean;
}

function PhotoPreview({
    file,
    onClear,
}: Readonly<{ file: File; onClear: () => void }>) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
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
                    alt={`Pratinjau ${file.name}`}
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
            <Button type="button" size="sm" variant="ghost" onClick={onClear}>
                Hapus
            </Button>
        </div>
    );
}

export function PhotoInput({
    id,
    value,
    onChange,
    accept = 'image/jpeg,image/png',
    buttonLabel = 'Ambil Foto',
    hint,
    capture = false,
}: Readonly<PhotoInputProps>) {
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    if (!Capacitor.isNativePlatform()) {
        return (
            <div className="grid gap-2">
                <input
                    ref={fileInputRef}
                    id={id}
                    type="file"
                    accept={accept}
                    capture={capture ? 'environment' : undefined}
                    className="sr-only"
                    onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                />
                <Button
                    type="button"
                    variant="outline"
                    className="min-h-11 w-full justify-start"
                    onClick={() => fileInputRef.current?.click()}
                >
                    {value ? 'Ganti Foto' : 'Pilih Foto'}
                </Button>
                {hint && !value && (
                    <p className="text-xs text-muted-foreground">{hint}</p>
                )}
                {value && (
                    <PhotoPreview file={value} onClear={() => onChange(null)} />
                )}
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <Button
                id={id}
                type="button"
                variant="outline"
                className="min-h-11 w-full"
                disabled={busy}
                onClick={async () => {
                    setBusy(true);
                    setError(null);

                    try {
                        onChange(await takePhoto());
                    } catch {
                        setError('Gagal mengambil foto. Coba lagi.');
                    } finally {
                        setBusy(false);
                    }
                }}
            >
                {busy ? 'Mengambil foto...' : buttonLabel}
            </Button>
            {hint && !value && (
                <p className="text-xs text-muted-foreground">{hint}</p>
            )}
            {value && (
                <PhotoPreview file={value} onClear={() => onChange(null)} />
            )}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
