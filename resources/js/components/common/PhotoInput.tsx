import {
    FilePreview,
    FileUploadInput,
} from '@/components/common/FileUploadInput';
import { Button } from '@/components/ui/button';
import { takePhoto } from '@/lib/camera';
import { Capacitor } from '@capacitor/core';
import { useState } from 'react';

interface PhotoInputProps {
    id: string;
    value: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    buttonLabel?: string;
    hint?: string;
    capture?: boolean;
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

    if (!Capacitor.isNativePlatform()) {
        return (
            <FileUploadInput
                id={id}
                value={value}
                onChange={onChange}
                accept={accept}
                capture={capture}
                showPreview
                hint={hint}
                buttonLabel={value ? 'Ganti Foto' : 'Pilih Foto'}
            />
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
                <FilePreview file={value} onClear={() => onChange(null)} />
            )}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
