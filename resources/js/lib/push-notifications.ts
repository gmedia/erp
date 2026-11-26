import axios from '@/lib/axios';
import { translateRegistered } from '@/utils/errorHandling';
import { Capacitor } from '@capacitor/core';
import { PushNotifications, type Token } from '@capacitor/push-notifications';
import { toast } from 'sonner';

let listenersAttached = false;

function pushToast(key: string, fallback: string): string {
    return translateRegistered(`push_toast.${key}`, fallback);
}

export type PushRegistrationResult = 'registered' | 'denied' | 'unsupported';

export function isPushSupported(): boolean {
    return Capacitor.isNativePlatform();
}

export async function enablePushNotifications(): Promise<PushRegistrationResult> {
    if (!Capacitor.isNativePlatform()) {
        return 'unsupported';
    }

    let permission = await PushNotifications.checkPermissions();

    if (permission.receive !== 'granted') {
        permission = await PushNotifications.requestPermissions();
    }

    if (permission.receive !== 'granted') {
        return 'denied';
    }

    if (!listenersAttached) {
        listenersAttached = true;

        await PushNotifications.addListener(
            'registration',
            async (token: Token) => {
                try {
                    await axios.post('/api/device-tokens', {
                        token: token.value,
                        platform: Capacitor.getPlatform(),
                    });
                    toast.success(
                        pushToast('enabled', 'Push notifications enabled.'),
                    );
                } catch {
                    toast.error(
                        pushToast(
                            'register_device_failed',
                            'Failed to register this device.',
                        ),
                    );
                }
            },
        );

        await PushNotifications.addListener('registrationError', () => {
            toast.error(
                pushToast(
                    'firebase_config_failed',
                    'Failed to enable notifications. Check the Firebase configuration.',
                ),
            );
        });

        await PushNotifications.addListener(
            'pushNotificationReceived',
            (notification) => {
                toast.info(
                    notification.title ??
                        pushToast('new_notification', 'New notification'),
                );
            },
        );

        await PushNotifications.addListener(
            'pushNotificationActionPerformed',
            (action) => {
                const target = action.notification.data?.target_url;

                if (typeof target === 'string' && target !== '') {
                    globalThis.history.pushState({}, '', target);
                    globalThis.dispatchEvent(new PopStateEvent('popstate'));
                }
            },
        );
    }

    if (Capacitor.getPlatform() === 'android') {
        await PushNotifications.createChannel({
            id: 'default',
            name: 'General',
            description: 'General notifications',
            importance: 5,
            visibility: 1,
            vibration: true,
        });
    }

    await PushNotifications.register();

    return 'registered';
}
