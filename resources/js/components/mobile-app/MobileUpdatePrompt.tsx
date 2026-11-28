import { PageBanner } from '@/components/common/PageBanner';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/contexts/i18n-context';
import { useMobileUpdateCheck } from '@/hooks/useMobileUpdateCheck';
import { Download, X } from 'lucide-react';

function openDownloadPage(url: string): void {
    globalThis.window.open(url, '_system');
}

export function MobileUpdatePrompt() {
    const { t } = useTranslation();
    const { optionalUpdate, mandatoryUpdate, dismissOptionalUpdate } =
        useMobileUpdateCheck();

    return (
        <>
            {optionalUpdate ? (
                <div
                    className="px-6 pt-4 md:px-4"
                    data-testid="mobile-update-banner"
                >
                    <PageBanner
                        tone="info"
                        title={t('mobileApp.update_available_title', {
                            version: optionalUpdate.versionName,
                        })}
                        action={
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={() =>
                                        openDownloadPage(
                                            optionalUpdate.downloadPageUrl,
                                        )
                                    }
                                    data-testid="mobile-update-banner-action"
                                >
                                    <Download aria-hidden="true" />
                                    {t('mobileApp.update_action')}
                                </Button>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label={t('common.close')}
                                    onClick={dismissOptionalUpdate}
                                    data-testid="mobile-update-banner-dismiss"
                                >
                                    <X aria-hidden="true" />
                                </Button>
                            </div>
                        }
                    >
                        {t('mobileApp.update_available_description')}
                    </PageBanner>
                </div>
            ) : null}

            <AlertDialog open={mandatoryUpdate !== null}>
                <AlertDialogContent
                    data-testid="mobile-update-mandatory-dialog"
                    onEscapeKeyDown={(event) => event.preventDefault()}
                >
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('mobileApp.update_required_title')}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {mandatoryUpdate
                                ? t('mobileApp.update_required_description', {
                                      version: mandatoryUpdate.versionName,
                                  })
                                : null}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <Button
                            type="button"
                            onClick={() => {
                                if (mandatoryUpdate) {
                                    openDownloadPage(
                                        mandatoryUpdate.downloadPageUrl,
                                    );
                                }
                            }}
                            data-testid="mobile-update-mandatory-action"
                        >
                            <Download aria-hidden="true" />
                            {t('mobileApp.update_required_action')}
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
