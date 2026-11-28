import axios from '@/lib/axios';
import { Capacitor } from '@capacitor/core';
import { useCallback, useEffect, useState } from 'react';

const LAST_CHECK_KEY = 'mobile_update_last_checked_at';
const CHECK_INTERVAL_MS = 6 * 60 * 60 * 1000;

interface LatestMobileRelease {
    version_name: string;
    version_code: number;
    min_supported_version_code: number;
    download_page_url: string;
}

export interface MobileUpdate {
    versionName: string;
    versionCode: number;
    minSupportedVersionCode: number;
    downloadPageUrl: string;
}

interface MobileUpdateState {
    optional: MobileUpdate | null;
    mandatory: MobileUpdate | null;
}

interface MobileUpdateWindow extends Window {
    __ERP_MOBILE_UPDATE_E2E__?: {
        forceNative?: boolean;
        versionCode?: string | number;
    };
}

function isNativeMobileApp(): boolean {
    if (Capacitor.isNativePlatform()) {
        return true;
    }

    // E2E can exercise the native flow in a browser without loading the
    // Capacitor native plugins (which need a real Android/iOS bridge).
    const testWindow = globalThis.window as MobileUpdateWindow | undefined;

    return testWindow?.__ERP_MOBILE_UPDATE_E2E__?.forceNative === true;
}

function readCurrentVersionCode(): number | null {
    const testWindow = globalThis.window as MobileUpdateWindow | undefined;
    const override = testWindow?.__ERP_MOBILE_UPDATE_E2E__?.versionCode;

    // E2E builds cannot change VITE_APP_VERSION_CODE at runtime, so tests may
    // provide the installed app version through this window flag.
    const raw =
        override === undefined || override === null
            ? (import.meta.env.VITE_APP_VERSION_CODE ?? '')
            : String(override);

    const parsed = Number.parseInt(raw.trim(), 10);

    return Number.isFinite(parsed) ? parsed : null;
}

function canCheckNow(): boolean {
    try {
        const raw = globalThis.localStorage.getItem(LAST_CHECK_KEY);
        const lastCheckedAt = Number.parseInt(raw ?? '', 10);

        if (!Number.isFinite(lastCheckedAt)) {
            return true;
        }

        return Date.now() - lastCheckedAt >= CHECK_INTERVAL_MS;
    } catch {
        return true;
    }
}

function markCheckedNow(): void {
    try {
        globalThis.localStorage.setItem(LAST_CHECK_KEY, String(Date.now()));
    } catch {
        // Storage can be unavailable in restricted webviews; checking again is harmless.
    }
}

function toMobileUpdate(release: LatestMobileRelease): MobileUpdate {
    return {
        versionName: release.version_name,
        versionCode: release.version_code,
        minSupportedVersionCode: release.min_supported_version_code,
        downloadPageUrl: release.download_page_url,
    };
}

export function useMobileUpdateCheck() {
    const [state, setState] = useState<MobileUpdateState>({
        optional: null,
        mandatory: null,
    });
    const [optionalDismissed, setOptionalDismissed] = useState(false);

    const checkForUpdate = useCallback(async (): Promise<void> => {
        if (!isNativeMobileApp()) {
            return;
        }

        const currentVersionCode = readCurrentVersionCode();

        if (currentVersionCode === null || !canCheckNow()) {
            return;
        }

        markCheckedNow();

        try {
            const response = await axios.get('/api/mobile-app/latest');
            const release = response.data?.data as
                | LatestMobileRelease
                | null
                | undefined;

            if (!release) {
                return;
            }

            const latestVersionCode = Number(release.version_code);
            const minSupportedVersionCode = Number(
                release.min_supported_version_code,
            );

            if (
                !Number.isFinite(latestVersionCode) ||
                latestVersionCode <= currentVersionCode
            ) {
                return;
            }

            const update = toMobileUpdate(release);
            const mandatory =
                Number.isFinite(minSupportedVersionCode) &&
                currentVersionCode < minSupportedVersionCode;

            setState({
                optional: mandatory ? null : update,
                mandatory: mandatory ? update : null,
            });
        } catch {
            // Network failures and missing releases stay silent by design.
        }
    }, []);

    useEffect(() => {
        void checkForUpdate();

        if (!isNativeMobileApp()) {
            return;
        }

        const handleVisibilityChange = (): void => {
            if (document.visibilityState === 'visible') {
                void checkForUpdate();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
        };
    }, [checkForUpdate]);

    return {
        optionalUpdate: optionalDismissed ? null : state.optional,
        mandatoryUpdate: state.mandatory,
        dismissOptionalUpdate: () => setOptionalDismissed(true),
    };
}
