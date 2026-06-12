import { describe, expect, it } from 'vitest'
import { formatDuration, formatPercent, initials, scoreTextClass, scoreTone } from '~/lib/format'

describe('formatDuration', () => {
  it('formats seconds, minutes and hours', () => {
    expect(formatDuration(45)).toBe('45s')
    expect(formatDuration(90)).toBe('1m 30s')
    expect(formatDuration(3661)).toBe('1h 01m')
  })

  it('handles null', () => {
    expect(formatDuration(null)).toBe('—')
    expect(formatDuration(undefined)).toBe('—')
  })
})

describe('formatPercent', () => {
  it('formats with given digits', () => {
    expect(formatPercent(72.5, 1)).toBe('72.5%')
    expect(formatPercent(72.5, 0)).toBe('73%')
  })
  it('handles null', () => {
    expect(formatPercent(null)).toBe('—')
  })
})

describe('initials', () => {
  it('takes the first two name parts', () => {
    expect(initials('Marco Rossi')).toBe('MR')
    expect(initials('Jane')).toBe('J')
    expect(initials('  ')).toBe('?')
    expect(initials(null)).toBe('?')
  })
})

describe('scoreTone / scoreTextClass', () => {
  it('buckets scores into tones', () => {
    expect(scoreTone(85)).toBe('success')
    expect(scoreTone(60)).toBe('warning')
    expect(scoreTone(20)).toBe('destructive')
  })
  it('maps to literal tailwind classes', () => {
    expect(scoreTextClass(85)).toBe('text-success')
    expect(scoreTextClass(60)).toBe('text-warning')
    expect(scoreTextClass(20)).toBe('text-destructive')
  })
})
