import { expect, test } from './fixtures';

const signedInPages: [path: string, title: RegExp][] = [
    ['/dashboard', /^Home/],
    ['/questionnaires', /^Plan/],
    ['/date-night/history', /^Date night history/],
    ['/moments', /^Moments/],
    ['/calendar', /^Shared calendar/],
    ['/love-notes', /^Love notes/],
    ['/relationship-hub', /^Relationship hub/],
    ['/settings/profile', /^Profile settings/],
];

for (const [path, title] of signedInPages) {
    test(`${path} loads for a signed-in user`, async ({ pageAs }) => {
        const page = await pageAs('alex');
        const response = await page.goto(path);

        expect(response?.status()).toBe(200);
        // The title is set by the page component, so it proves the page mounted.
        await expect(page).toHaveTitle(title);
    });
}

for (const [path, heading] of [
    ['/privacy', 'Privacy'],
    ['/terms', 'Terms'],
] as const) {
    test(`${path} loads for a guest`, async ({ page }) => {
        const response = await page.goto(path);

        expect(response?.status()).toBe(200);
        await expect(
            page.getByRole('heading', { level: 1, name: heading }),
        ).toBeVisible();
    });
}

test('an unknown URL shows the app 404 page', async ({ page }) => {
    const response = await page.goto('/no-such-page');

    expect(response?.status()).toBe(404);
    await expect(
        page.getByRole('heading', { level: 1, name: 'Page not found' }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Back to Date Night' }),
    ).toBeVisible();
});
