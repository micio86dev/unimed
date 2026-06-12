import { expect, test } from '@playwright/test'

test.describe('Self-registration', () => {
  test('a new student can register and is logged straight in', async ({ page }) => {
    await page.goto('/register')

    const email = `e2e-${Date.now()}@unimed.app`
    await page.getByLabel('Full name').fill('E2E Student')
    await page.getByLabel('Email').fill(email)
    // Two password fields (password + confirm) — fill by placeholder order.
    const pwd = page.getByPlaceholder('••••••••')
    await pwd.nth(0).fill('password123')
    await pwd.nth(1).fill('password123')
    await page.getByRole('button', { name: 'Sign up' }).click()

    // Active account + auto-login → straight to the dashboard.
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByText('Performance by subject')).toBeVisible()
  })

  test('the login page links to registration', async ({ page }) => {
    await page.goto('/login')
    await page.getByRole('link', { name: 'Create one' }).click()
    await expect(page).toHaveURL(/\/register/)
  })
})

test.describe('Language switch', () => {
  test('switches the UI to Italian and back', async ({ page }) => {
    await page.goto('/login')
    await page.getByRole('button', { name: 'Student', exact: true }).click()
    await page.getByRole('button', { name: 'Sign in' }).click()
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByText('Performance by subject')).toBeVisible()

    // Flip to Italian via the header switcher.
    await page.getByRole('button', { name: 'IT', exact: true }).click()
    await expect(page.getByText('Performance per materia')).toBeVisible()

    // And back to English.
    await page.getByRole('button', { name: 'EN', exact: true }).click()
    await expect(page.getByText('Performance by subject')).toBeVisible()
  })
})
