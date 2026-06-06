import { expect, test } from '@playwright/test';
import { seedBaseData } from '../support/seed';
import { loginAsAdmin } from '../support/auth';

test.describe('Authentication and route guards', () => {
  test('guest is redirected from admin routes', async ({ page }) => {
    /* Arrange */
    seedBaseData();

    /* Act */
    await page.goto('/');
    await page.goto('/admin');

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/login/);
  });

  test('guest is redirected from OAuth redirect route', async ({ page }) => {
    /* Arrange */
    seedBaseData();

    /* Act */
    await page.goto('/auth/instagram/redirect');

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/login/);
  });

  test('login form shows required validation', async ({ page }) => {
    /* Arrange */
    seedBaseData();

    /* Act */
    await page.goto('/admin/login');
    await page.getByRole('button', { name: /sign in/i }).click();

    /* Assert */
    await expect(page.getByLabel('Email address').locator('..').getByText(/required/i)).toBeVisible();
  });

  test('authenticated user can login and logout', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();

    /* Act */
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);
    await page.getByRole('button', { name: /user menu/i }).click();
    await page.getByRole('menuitem', { name: /sign out/i }).click();

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/login/);
  });
});
