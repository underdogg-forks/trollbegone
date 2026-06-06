import { expect, test } from '@playwright/test';
import { seedBaseData } from '../support/seed';
import { loginAsAdmin } from '../support/auth';

test.describe('Blocked accounts relation manager', () => {
  test('block (create) one single commenter and edit reason', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);
    await page.goto(`/admin/accounts/${ctx.accountId}`);
    await page.getByRole('tab', { name: /blocked accounts/i }).click();

    /* Act */
    await page.getByRole('button', { name: /new blocked account/i }).click();
    await page.getByRole('button', { name: /^create$/i }).click();

    /* Assert */
    await expect(page.getByText(/required/i).first()).toBeVisible();

    /* Act */
    await page.getByLabel('Blocked Username').fill('single_ui_troll');
    await page.getByLabel('Reason').fill('Single troll block');
    await page.getByRole('button', { name: /^create$/i }).click();

    /* Assert */
    await expect(page.getByText('single_ui_troll')).toBeVisible();

    /* Act */
    const row = page.getByRole('row', { name: /single_ui_troll/i });
    await row.getByRole('button', { name: /edit/i }).click();
    await page.getByLabel('Reason').fill('Single troll block updated');
    await page.getByRole('button', { name: /^save changes$/i }).click();

    /* Assert */
    await expect(page.getByText('Single troll block updated')).toBeVisible();
  });

  test('create multiple blocked commenters and search filters results', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);
    await page.goto(`/admin/accounts/${ctx.accountId}`);
    await page.getByRole('tab', { name: /blocked accounts/i }).click();

    /* Act */
    await page.getByRole('button', { name: /new blocked account/i }).click();
    await page.getByLabel('Blocked Username').fill('alpha_troll');
    await page.getByLabel('Reason').fill('Alpha reason');
    await page.getByRole('button', { name: /^create$/i }).click();
    await page.getByRole('button', { name: /new blocked account/i }).click();
    await page.getByLabel('Blocked Username').fill('beta_troll');
    await page.getByLabel('Reason').fill('Beta reason');
    await page.getByRole('button', { name: /^create$/i }).click();

    /* Assert */
    await expect(page.getByText('alpha_troll')).toBeVisible();
    await expect(page.getByText('beta_troll')).toBeVisible();

    /* Act */
    await page.getByPlaceholder(/search/i).fill('alpha_');

    /* Assert */
    await expect(page.getByText('alpha_troll')).toBeVisible();
    await expect(page.getByText('beta_troll')).not.toBeVisible();
  });
});
