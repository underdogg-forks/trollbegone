import { expect, test } from '@playwright/test';
import { seedBaseData } from '../support/seed';
import { loginAsAdmin } from '../support/auth';

test.describe('Accounts resource CRUD and table interactions', () => {
  test('create account validation and happy path', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);

    /* Act */
    await page.goto('/admin/accounts');
    await page.getByRole('button', { name: /new instagram account/i }).click();
    await page.getByRole('button', { name: /^create$/i }).click();

    /* Assert */
    await expect(page.getByLabel('Username').locator('..').getByText(/required/i)).toBeVisible();

    /* Act */
    await page.getByLabel('Username').fill('created_account');
    await page.getByLabel('Instagram ID').fill('ig-created');
    await page.getByLabel('Access Token').fill('stored_token_001');
    await page.getByRole('button', { name: /^create$/i }).click();

    /* Assert */
    await expect(page.getByText('created_account')).toBeVisible();
  });

  test('search and edit account from index/detail pages', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);

    /* Act */
    await page.goto('/admin/accounts');
    await page.getByPlaceholder(/search/i).fill('secondary_');

    /* Assert */
    await expect(page.getByText('secondary_account')).toBeVisible();

    /* Act */
    await page.getByRole('row', { name: /secondary_account/i }).getByRole('link', { name: /view|detail|secondary_account/i }).click();
    await page.getByRole('button', { name: /^edit$/i }).click();
    await page.getByLabel('Username').fill('secondary_account_updated');
    await page.getByRole('button', { name: /^save changes$/i }).click();

    /* Assert */
    await expect(page.getByText('secondary_account_updated')).toBeVisible();
  });

  test('delete account from detail page', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);

    /* Act */
    await page.goto(`/admin/accounts/${ctx.accountId}`);
    await page.getByRole('button', { name: /^delete$/i }).click();
    await page.getByRole('button', { name: /^confirm$/i }).click();

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/accounts$/);
    await expect(page.getByRole('row', { name: /primary_account/i })).not.toBeVisible();
  });
});
