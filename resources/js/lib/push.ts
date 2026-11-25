import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { store } from '@/routes/device-tokens';
import { index as notificationsIndex } from '@/routes/notifications';

/**
 * Inside the Capacitor app: ask for push permission once someone is signed in
 * and hand the phone's FCM token to the server. Does nothing in a browser.
 */
export function initializePushNotifications(): void {
    if (!Capacitor.isNativePlatform()) {
        return;
    }

    PushNotifications.addListener('registration', ({ value }) => {
        axios
            .post(store().url, {
                token: value,
                platform: Capacitor.getPlatform(),
            })
            .catch(() => {});
    });

    // Android shows nothing for a push that arrives while the app is open
    PushNotifications.addListener('pushNotificationReceived', (push) => {
        toast(push.title ?? 'New notification', { description: push.body });
        router.reload();
    });

    PushNotifications.addListener('pushNotificationActionPerformed', () => {
        router.visit(notificationsIndex().url);
    });

    let registeredFor: number | null = null;

    router.on('navigate', async (event) => {
        const userId =
            (event.detail.page.props.auth as { user?: { id: number } | null })
                ?.user?.id ?? null;

        if (userId === registeredFor) {
            return;
        }

        registeredFor = userId;

        if (userId === null) {
            return;
        }

        const { receive } = await PushNotifications.requestPermissions();

        if (receive === 'granted') {
            await PushNotifications.register();
        }
    });
}
