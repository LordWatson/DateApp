import { defineConfig } from '@playwright/test';
import { baseURL, port, serverEnv } from './tests/e2e/env';

export default defineConfig({
    testDir: 'tests/e2e',
    globalSetup: './tests/e2e/global-setup.ts',
    // PHP's built-in server handles one request at a time and the tests share one SQLite file.
    workers: 1,
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    timeout: 60_000,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        channel: 'chrome',
        headless: true,
        viewport: { width: 414, height: 896 },
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        { name: 'chrome', testMatch: /.*\.spec\.ts/, dependencies: ['setup'] },
    ],
    webServer: {
        command: `php -S 127.0.0.1:${port} -t public tests/e2e/server.php`,
        // A static file: the app itself cannot answer until global setup has migrated the database.
        url: `${baseURL}/robots.txt`,
        env: serverEnv,
        reuseExistingServer: false,
        stdout: 'ignore',
        stderr: 'ignore',
        timeout: 30_000,
    },
});
