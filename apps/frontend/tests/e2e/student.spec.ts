import { expect, test } from '@playwright/test'

test.describe('Student journey', () => {
  test('logs in, takes a quiz and sees results', async ({ page }) => {
    // --- Login ---
    await page.goto('/login')
    await page.getByRole('button', { name: 'Student', exact: true }).click()
    await page.getByRole('button', { name: 'Sign in' }).click()

    // --- Dashboard ---
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByText('Performance by subject')).toBeVisible()

    // --- Browse quizzes ---
    await page.getByRole('link', { name: 'Quizzes' }).first().click()
    await expect(page).toHaveURL(/\/quizzes/)
    await page.getByRole('button', { name: 'Start', exact: true }).first().click()

    // --- Quiz detail → start ---
    await expect(page.getByRole('button', { name: /Start simulation/ })).toBeVisible()
    await page.getByRole('button', { name: /Start simulation/ }).click()

    // --- Take the quiz ---
    await expect(page).toHaveURL(/\/attempt\//)
    await expect(page.getByText(/Question 1 of/)).toBeVisible()

    // Answer the first few questions, advancing with Next.
    for (let i = 0; i < 3; i++) {
      await page.getByTestId('answer-option').first().click()
      const next = page.getByRole('button', { name: 'Next' })
      if (await next.isVisible().catch(() => false)) await next.click()
    }

    // --- Submit (a Submit button opens the confirmation modal) ---
    await page.getByRole('button', { name: 'Submit quiz' }).first().click()
    await expect(page.getByRole('button', { name: 'Keep going' })).toBeVisible()
    await page.getByRole('button', { name: 'Submit quiz' }).last().click()

    // --- Results ---
    await expect(page).toHaveURL(/\/results\//)
    await expect(page.getByText('Answer review')).toBeVisible()
    await expect(page.getByText('Performance by subject')).toBeVisible()
  })

  test('can open the leaderboard', async ({ page }) => {
    await page.goto('/login')
    await page.getByRole('button', { name: 'Student', exact: true }).click()
    await page.getByRole('button', { name: 'Sign in' }).click()
    await expect(page).toHaveURL(/\/dashboard/)

    await page.getByRole('link', { name: 'Rankings' }).first().click()
    await expect(page).toHaveURL(/\/rankings/)
    await expect(page.getByText('Leaderboard')).toBeVisible()
  })
})
