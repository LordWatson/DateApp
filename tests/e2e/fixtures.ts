import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authFile, baseURL } from './env';
import type { UserKey } from './env';

interface Fixtures {
    /** Uncaught errors from every page the test opened; any entry fails the test. */
    pageErrors: string[];
    /** Opens a page already signed in as one of the demo couple. */
    pageAs: (user: UserKey) => Promise<Page>;
}

async function watch(page: Page, pageErrors: string[]): Promise<void> {
    page.on('pageerror', (error) =>
        pageErrors.push(`${page.url()}: ${error.message}`),
    );
    // Nothing outside the app under test is reached (fonts, geocoding).
    await page.route(
        (url) => url.origin !== baseURL,
        (route) => route.abort(),
    );
}

export const test = base.extend<Fixtures>({
    pageErrors: [
        async ({}, use) => {
            const pageErrors: string[] = [];
            await use(pageErrors);
            expect(pageErrors, 'uncaught page errors').toEqual([]);
        },
        { auto: true },
    ],
    page: async ({ page, pageErrors }, use) => {
        await watch(page, pageErrors);
        await use(page);
    },
    pageAs: async ({ browser, pageErrors }, use) => {
        const pages: Page[] = [];

        await use(async (user) => {
            const context = await browser.newContext({
                baseURL,
                storageState: authFile(user),
                viewport: { width: 414, height: 896 },
            });
            const page = await context.newPage();
            await watch(page, pageErrors);
            pages.push(page);

            return page;
        });

        for (const page of pages) {
            await page.context().close();
        }
    },
});

export { expect };

export async function logIn(
    page: Page,
    email: string,
    password: string,
): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
}
