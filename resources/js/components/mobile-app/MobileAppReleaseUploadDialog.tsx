import { FileUploadInput } from '@/components/common/FileUploadInput';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/contexts/i18n-context';
import { useCreateMobileAppRelease } from '@/hooks/useMobileAppReleases';
import { mapFirstValidationErrors } from '@/utils/errorHandling';
import { Loader2, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';

interface MobileAppReleaseUploadDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

interface FormErrors {
    file?: string;
    version_name?: string;
    version_code?: string;
    min_supported_version_code?: string;
    notes?: string;
}

const INITIAL_ERRORS: FormErrors = {};

export function MobileAppReleaseUploadDialog({
    open,
    onOpenChange,
}: Readonly<MobileAppReleaseUploadDialogProps>) {
    const { t } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [versionName, setVersionName] = useState('');
    const [versionCode, setVersionCode] = useState('');
    const [minVersionCode, setMinVersionCode] = useState('');
    const [notes, setNotes] = useState('');
    const [errors, setErrors] = useState<FormErrors>(INITIAL_ERRORS);
    const [progress, setProgress] = useState(0);

    const createRelease = useCreateMobileAppRelease(setProgress);

    useEffect(() => {
        if (open) return;

        setFile(null);
        setVersionName('');
        setVersionCode('');
        setMinVersionCode('');
        setNotes('');
        setErrors(INITIAL_ERRORS);
        setProgress(0);
    }, [open]);

    const validate = (): FormErrors => {
        const next: FormErrors = {};
        const parsedVersionCode = Number(versionCode);
        const parsedMinVersionCode = Number(minVersionCode);

        if (!file) {
            next.file = t('mobileApp.file_required');
        } else if (!file.name.toLowerCase().endsWith('.apk')) {
            next.file = t('mobileApp.file_invalid_extension');
        }

        if (!versionName.trim()) {
            next.version_name = t('mobileApp.version_name_required');
        }

        if (!Number.isInteger(parsedVersionCode) || parsedVersionCode < 1) {
            next.version_code = t('mobileApp.version_code_required');
        }

        if (
            !Number.isInteger(parsedMinVersionCode) ||
            parsedMinVersionCode < 1
        ) {
            next.min_supported_version_code = t(
                'mobileApp.min_version_code_required',
            );
        } else if (
            Number.isInteger(parsedVersionCode) &&
            parsedMinVersionCode > parsedVersionCode
        ) {
            next.min_supported_version_code = t(
                'mobileApp.min_version_exceeds',
            );
        }

        return next;
    };

    const handleSubmit = () => {
        const nextErrors = validate();
        setErrors(nextErrors);

        if (Object.keys(nextErrors).length > 0 || !file) return;

        const payload = new FormData();
        payload.append('file', file);
        payload.append('version_name', versionName.trim());
        payload.append('version_code', String(Number(versionCode)));
        payload.append(
            'min_supported_version_code',
            String(Number(minVersionCode)),
        );
        if (notes.trim()) {
            payload.append('notes', notes.trim());
        }

        createRelease.mutate(payload, {
            onSuccess: () => {
                onOpenChange(false);
            },
            onError: (error) => {
                setProgress(0);
                setErrors(mapFirstValidationErrors(error));
            },
        });
    };

    const submitting = createRelease.isPending;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t('mobileApp.upload_title')}</DialogTitle>
                    <DialogDescription>
                        {t('mobileApp.upload_description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="mobile_app_apk">
                            {t('mobileApp.field_file')}
                        </Label>
                        <FileUploadInput
                            id="mobile_app_apk"
                            accept=".apk,application/vnd.android.package-archive,application/zip"
                            value={file}
                            onChange={setFile}
                            hint={t('mobileApp.field_file_hint')}
                            disabled={submitting}
                        />
                        {errors.file ? (
                            <p className="text-xs text-destructive-foreground">
                                {errors.file}
                            </p>
                        ) : null}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="version_name">
                                {t('mobileApp.field_version_name')}
                            </Label>
                            <Input
                                id="version_name"
                                name="version_name"
                                value={versionName}
                                onChange={(event) =>
                                    setVersionName(event.target.value)
                                }
                                placeholder={t(
                                    'mobileApp.version_name_placeholder',
                                )}
                                disabled={submitting}
                            />
                            {errors.version_name ? (
                                <p className="text-xs text-destructive-foreground">
                                    {errors.version_name}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="version_code">
                                {t('mobileApp.field_version_code')}
                            </Label>
                            <Input
                                id="version_code"
                                name="version_code"
                                type="number"
                                min={1}
                                value={versionCode}
                                onChange={(event) =>
                                    setVersionCode(event.target.value)
                                }
                                placeholder={t(
                                    'mobileApp.version_code_placeholder',
                                )}
                                disabled={submitting}
                            />
                            {errors.version_code ? (
                                <p className="text-xs text-destructive-foreground">
                                    {errors.version_code}
                                </p>
                            ) : (
                                <p className="text-xs text-muted-foreground">
                                    {t('mobileApp.version_code_helper')}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="min_supported_version_code">
                            {t('mobileApp.field_min_version_code')}
                        </Label>
                        <Input
                            id="min_supported_version_code"
                            name="min_supported_version_code"
                            type="number"
                            min={1}
                            value={minVersionCode}
                            onChange={(event) =>
                                setMinVersionCode(event.target.value)
                            }
                            placeholder={t(
                                'mobileApp.min_version_code_placeholder',
                            )}
                            disabled={submitting}
                        />
                        {errors.min_supported_version_code ? (
                            <p className="text-xs text-destructive-foreground">
                                {errors.min_supported_version_code}
                            </p>
                        ) : null}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('mobileApp.field_notes')}
                        </Label>
                        <Textarea
                            id="notes"
                            name="notes"
                            value={notes}
                            onChange={(event) => setNotes(event.target.value)}
                            rows={3}
                            disabled={submitting}
                        />
                    </div>

                    {submitting ? (
                        <div className="space-y-1">
                            <Progress value={progress} />
                            <p className="text-xs text-muted-foreground">
                                {t('mobileApp.upload_progress', {
                                    percent: String(progress),
                                })}
                            </p>
                        </div>
                    ) : null}
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                        disabled={submitting}
                    >
                        {t('common.cancel')}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleSubmit}
                        disabled={submitting}
                        data-testid="mobile-app-upload-submit"
                    >
                        {submitting ? (
                            <Loader2
                                className="animate-spin"
                                aria-hidden="true"
                            />
                        ) : (
                            <Upload aria-hidden="true" />
                        )}
                        {submitting
                            ? t('mobileApp.uploading')
                            : t('mobileApp.submit_upload')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
