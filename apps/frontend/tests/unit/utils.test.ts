import { describe, expect, it } from 'vitest'
import { cn } from '~/lib/utils'

describe('cn', () => {
  it('merges class names', () => {
    expect(cn('p-2', 'text-sm')).toBe('p-2 text-sm')
  })

  it('resolves conflicting tailwind classes (last wins)', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4')
    expect(cn('text-red-500', 'text-blue-500')).toBe('text-blue-500')
  })

  it('handles conditional values', () => {
    expect(cn('base', false && 'hidden', 'extra')).toBe('base extra')
  })
})
