import { users, password, authFile } from './env';
import type { UserKey } from './env';
import { test as setup, expect, logIn } from './fixtures';

// Login is rate limited to 5 a minute per account, so each demo user signs in once and the tests reuse the session.
for (const user of Object.keys(users) as UserKey[]) {
    setup(`sign in as ${user}`, async ({ page }) => {
        await logIn(page, users[user].email, password);
        await expect(page).toHaveURL(/\/dashboard$/);
        await page.context().storageState({ path: authFile(user) });
    });
}
