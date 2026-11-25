import { password } from './env';
import { expect, test } from './fixtures';

// Runs last (file name): it ends the demo couple's connection, which the other specs rely on.
test('a couple pauses and resumes, then ends the connection choosing what to keep', async ({
    pageAs,
}) => {
    const alex = await pageAs('alex');
    const rimma = await pageAs('rimma');

    await alex.goto('/partner');
    await alex.getByRole('button', { name: 'Pause connection' }).click();
    await alex.getByRole('button', { name: 'Pause', exact: true }).click();
    await expect(
        alex.getByRole('heading', { name: 'Your connection is paused' }),
    ).toBeVisible();

    // The other partner sees the pause and cannot lift it.
    await rimma.goto('/partner');
    await expect(rimma.getByText(/Only .+ can resume it\./)).toBeVisible();
    await expect(
        rimma.getByRole('button', { name: 'Resume connection' }),
    ).toHaveCount(0);

    await alex.getByRole('button', { name: 'Resume connection' }).click();
    await expect(
        alex.getByRole('button', { name: 'Pause connection' }),
    ).toBeVisible();

    // Ending asks what to keep and for the password.
    await alex.getByRole('button', { name: 'Disconnect partner' }).click();
    await alex.getByLabel(/Keep the love notes between you/).uncheck();
    await alex.getByLabel('Your password, to confirm').fill(password);
    await alex.getByRole('button', { name: 'Disconnect', exact: true }).click();
    await expect(
        alex.getByRole('link', { name: 'Choose what you keep' }),
    ).toBeVisible();

    // The other person makes their own choice afterwards.
    await rimma.goto('/partner/after');
    await expect(
        rimma.getByRole('heading', { name: 'What you keep' }),
    ).toBeVisible();
    await rimma.getByRole('button', { name: 'Save my choices' }).click();
    await expect(rimma).toHaveURL(/\/partner$/);
});
