import { expect, test } from '@playwright/test';
import { seedBaseData } from '../support/seed';
import { loginAsAdmin } from '../support/auth';

test.describe('Posts and comments moderation pages', () => {
  test('shows posts index page for an account user', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);

    /* Act */
    await page.goto(`/admin/accounts/${ctx.accountId}/posts/friend_account`);

    /* Assert */
    await expect(page.getByText(/posts/i).first()).toBeVisible();
  });

  test('shows comments for a post and commenters list for a single tag workflow route', async ({ page }) => {
    /* Arrange */
    const ctx = seedBaseData();
    await loginAsAdmin(page, ctx.userEmail, ctx.userPassword);

    /* Act */
    await page.goto(`/admin/accounts/${ctx.accountId}/comments/post-1`);

    /* Assert */
    await expect(page.getByText(/comments/i).first()).toBeVisible();

    /* Act */
    await page.goto(`/admin/accounts/${ctx.accountId}/commenters/post-1`);

    /* Assert */
    await expect(page.getByText(/commenters/i).first()).toBeVisible();
  });
});
