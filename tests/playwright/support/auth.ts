import { expect, type Page } from '@playwright/test';

export const loginAsAdmin = async (page: Page, email: string, password: string): Promise<void> => {
  await page.goto('/admin/login');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: /sign in/i }).click();
  await expect(page).toHaveURL(/\/admin/);
};
