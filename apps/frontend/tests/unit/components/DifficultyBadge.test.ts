import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import DifficultyBadge from '~/components/DifficultyBadge.vue'

describe('DifficultyBadge', () => {
  it('renders the correct label per difficulty', async () => {
    const easy = await mountSuspended(DifficultyBadge, { props: { difficulty: 'easy' } })
    expect(easy.text()).toBe('Easy')

    const medium = await mountSuspended(DifficultyBadge, { props: { difficulty: 'medium' } })
    expect(medium.text()).toBe('Medium')

    const hard = await mountSuspended(DifficultyBadge, { props: { difficulty: 'hard' } })
    expect(hard.text()).toBe('Hard')
  })
})
