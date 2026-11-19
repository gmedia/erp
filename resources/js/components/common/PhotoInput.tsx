import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { takePhoto } from '@/lib/camera';
import { Capacitor } from '@capacitor/core';
import { useState } from 'react';

interface PhotoInputProps {
    id: string;
    value: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    buttonLabel?: string;
}

export function PhotoInput({
    id,
    value,
    onChange,
    accept = 'image/jpeg,image/png',
    buttonLabel = 'Ambil Foto',
}: PhotoInputProps) {
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    if (!Capacitor.isNativePlatform()) {
        return (
            <Input
                id={id}
                type="file"
                accept={accept}
                onChange={(e) => onChange(e.target.files?.[0] ?? null)}
            />
        );
    }

    return (
        <div className="space-y-2">
            <Button
                id={id}
                type="button"
                variant="outline"
                className="w-full"
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
            {value && (
                <p className="text-sm text-muted-foreground">{value.name}</p>
            )}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
