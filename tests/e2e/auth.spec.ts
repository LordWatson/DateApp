import { password, users } from './env';
import { expect, logIn, test } from './fixtures';

test('a guest is sent to login, signs in and reaches the dashboard', async ({
    page,
}) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);

    await logIn(page, users.alex.email, password);

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page).toHaveTitle(/^Home/);
    await expect(
        page.getByRole('heading', { level: 1, name: users.alex.name }),
    ).toBeVisible();
});

test('a new user registers and is asked to verify their email', async ({
    page,
}) => {
    await page.goto('/register');
    await page.getByLabel('Name').fill('Nova Tester');
    await page
        .getByLabel('Email address')
        .fill(`nova-${Date.now()}@example.com`);
    await page
        .getByLabel('Password', { exact: true })
        .fill('Str0ng!Passw0rd-e2e');
    await page.getByLabel('Confirm password').fill('Str0ng!Passw0rd-e2e');
    await page.getByRole('checkbox', { name: /I agree to the/ }).check();
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page).toHaveURL(/\/email\/verify$/);
    await expect(page).toHaveTitle(/^Email verification/);
});
