import axios from '@/lib/axios';
import { Capacitor } from '@capacitor/core';
import { PushNotifications, type Token } from '@capacitor/push-notifications';
import { toast } from 'sonner';

let listenersAttached = false;

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
                    toast.success('Push notifications enabled.');
                } catch {
                    toast.error('Failed to register this device.');
                }
            },
        );

        await PushNotifications.addListener('registrationError', () => {
            toast.error(
                'Failed to enable notifications. Check the Firebase configuration.',
            );
        });

        await PushNotifications.addListener(
            'pushNotificationReceived',
            (notification) => {
                toast.info(notification.title ?? 'New notification');
            },
        );

        await PushNotifications.addListener(
            'pushNotificationActionPerformed',
            (action) => {
                const target = action.notification.data?.target_url;

                if (typeof target === 'string' && target !== '') {
                    globalThis.location.assign(target);
                }
            },
        );
    }

    await PushNotifications.register();

    return 'registered';
}
