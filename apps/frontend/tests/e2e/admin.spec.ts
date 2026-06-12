import { expect, test } from '@playwright/test'

async function loginAsAdmin(page: import('@playwright/test').Page) {
  await page.goto('/login')
  await page.getByRole('button', { name: 'Admin', exact: true }).click()
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).toHaveURL(/\/admin/)
}

test.describe('Admin journey', () => {
  test('sees the analytics overview', async ({ page }) => {
    await loginAsAdmin(page)
    await expect(page.getByText('Admin overview')).toBeVisible()
    await expect(page.getByText('Hardest subjects')).toBeVisible()
    await expect(page.getByText('Total students')).toBeVisible()
  })

  test('creates a question', async ({ page }) => {
    await loginAsAdmin(page)
    await page.goto('/admin/questions')
    await expect(page.getByRole('button', { name: 'New question' })).toBeVisible()

    await page.getByRole('button', { name: 'New question' }).click()
    const stem = `E2E test question ${Date.now()}`
    await page.getByPlaceholder('Enter the question stem…').fill(stem)
    await page.getByPlaceholder('Answer 1').fill('Correct answer')
    await page.getByPlaceholder('Answer 2').fill('Wrong answer')
    await page.getByRole('button', { name: 'Create question' }).click()

    await expect(page.getByText('Question created')).toBeVisible()
  })

  test('creates an auto-generated quiz', async ({ page }) => {
    await loginAsAdmin(page)
    await page.goto('/admin/quizzes')
    await expect(page.getByRole('button', { name: 'New quiz' })).toBeVisible()

    await page.getByRole('button', { name: 'New quiz' }).click()
    const title = `E2E Quiz ${Date.now()}`
    await page.getByPlaceholder('e.g. Biology Mock Exam').fill(title)
    await page.getByRole('button', { name: 'Create quiz' }).click()

    await expect(page.getByText('Quiz created')).toBeVisible()
  })
})
