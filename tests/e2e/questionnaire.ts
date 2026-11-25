import type { Page } from '@playwright/test';
import { expect } from './fixtures';

/**
 * Starts a questionnaire, gives the first possible answer to every question
 * and presses "Complete questionnaire" on the summary.
 */
export async function completeQuestionnaire(
    page: Page,
    slug: string,
    mode?: string,
): Promise<void> {
    await page.goto(`/questionnaires/${slug}`);

    if (mode) {
        await page.getByRole('radio', { name: mode }).click();
    }

    await page
        .getByRole('button', {
            name: /^(Start|Continue|Restart) questionnaire$/,
        })
        .first()
        .click();

    for (let answered = 0; answered < 40; answered++) {
        await page.waitForURL(/\/(question\/\d+|summary)$/);

        const url = page.url();
        const order = url.match(/\/question\/(\d+)$/)?.[1];

        if (!order) {
            break;
        }

        // The title is set by the question page, so the options on screen belong to this question.
        await expect(page).toHaveTitle(new RegExp(`^Question ${order} of `));
        await answerQuestion(page);
        await page.waitForURL((next) => next.href !== url);
    }

    await page.getByRole('button', { name: 'Complete questionnaire' }).click();
}

async function answerQuestion(page: Page): Promise<void> {
    const radio = page.getByRole('radio', { checked: false }).first();
    const checkbox = page.getByRole('checkbox', { checked: false }).first();
    const slider = page.getByRole('slider');
    const textbox = page.getByRole('textbox');

    await radio.or(checkbox).or(slider).or(textbox).first().waitFor();

    if (await radio.isVisible()) {
        // A single choice saves and moves on by itself.
        await radio.click();

        return;
    }

    if (await checkbox.isVisible()) {
        await checkbox.click();
    } else if (await slider.isVisible()) {
        await slider.focus();
        await slider.press('ArrowRight');
    } else {
        await textbox.fill('Somewhere cosy');
    }

    // Answers save in the background; leaving before that finishes could lose one.
    await expect(page.getByText('Saved', { exact: true })).toBeVisible();
    await page
        .getByRole('button', { name: /^(Continue|Review answers)$/ })
        .click();
}
