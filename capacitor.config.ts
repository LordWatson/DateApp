import type { CapacitorConfig } from '@capacitor/cli';

// The app is rendered by Laravel (Inertia), so the native shell is a WebView on the live site.
// Set CAP_SERVER_URL before `npx cap sync`: the production URL for a release build,
// or e.g. http://10.0.2.2:8000 for the Android emulator against `php artisan serve`.
const serverUrl = process.env.CAP_SERVER_URL;

if (!serverUrl) {
    throw new Error(
        'Set CAP_SERVER_URL to the URL the mobile app should load.',
    );
}

const config: CapacitorConfig = {
    appId: 'com.datenight.app',
    appName: 'DateNight',
    webDir: 'resources/capacitor',
    server: {
        url: serverUrl,
        cleartext: serverUrl.startsWith('http://'),
    },
};

export default config;
