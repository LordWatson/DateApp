import { users } from './env';
import { expect, test } from './fixtures';
import { completeQuestionnaire } from './questionnaire';

test('both partners answer a couple questionnaire and both see the date night plan', async ({
    pageAs,
}) => {
    const alex = await pageAs('alex');
    const rimma = await pageAs('rimma');

    await completeQuestionnaire(alex, 'tonight');
    await expect(alex).toHaveURL(/\/questionnaires\/tonight\/complete$/);
    await expect(
        alex.getByText(`We've let ${users.rimma.name} know.`),
    ).toBeVisible();

    // The queue is sync and AI is off, so the built-in plan exists as soon as the second partner finishes.
    await completeQuestionnaire(rimma, 'tonight');
    await expect(rimma).toHaveURL(/\/date-night\/\d+$/);
    await expect(rimma.getByText('Your perfect evening')).toBeVisible();
    await expect(rimma.getByRole('heading', { level: 1 })).not.toBeEmpty();

    await alex.goto(new URL(rimma.url()).pathname);
    await expect(alex.getByText('Your perfect evening')).toBeVisible();
    await expect(alex.getByRole('heading', { level: 1 })).not.toBeEmpty();
});

test('a solo questionnaire produces a plan straight away', async ({
    pageAs,
}) => {
    const alex = await pageAs('alex');

    await completeQuestionnaire(alex, 'plan-a-date-out', 'Just me');

    await expect(alex).toHaveURL(/\/date-night\/\d+$/);
    await expect(alex.getByText('Your perfect evening')).toBeVisible();
    await expect(alex.getByRole('heading', { level: 1 })).not.toBeEmpty();
});
