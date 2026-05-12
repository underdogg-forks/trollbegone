import { expect, test } from '@playwright/test';
import { seedBaseData } from '../support/seed';
import { loginAsAdmin } from '../support/auth';

test.describe('Authentication and route guards', () => {
  test('guest is redirected from authenticated routes and login validates', async ({ page }) => {
    /* Arrange */
    seedBaseData();

    /* Act */
    await page.goto('/');
    await page.goto('/admin');

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/login/);

    /* Act */
    await page.goto('/auth/instagram/redirect');

    /* Assert */
    await expect(page).toHaveURL(/\/admin\/login/);

    /* Act */
    await page.getByRole('button', { name: /sign in/i }).click();

    /* Assert */
    await expect(page.getByText(/required/i).first()).toBeVisible();
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
