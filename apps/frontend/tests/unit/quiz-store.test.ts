import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { TakePayload } from '~/types'

// Stub the API client so autosave calls resolve without a network request.
mockNuxtImport('useApi', () => () => vi.fn(() => Promise.resolve({})))

function payload(): TakePayload {
  return {
    attempt: {
      id: 1,
      quiz: { id: 1, title: 'Test', slug: 'test', time_limit_minutes: null, question_count: 2 },
      status: 'in_progress',
      total_questions: 2,
      correct_count: 0,
      incorrect_count: 0,
      unanswered_count: 0,
      percentage: 0,
      points: 0,
      time_spent_seconds: null,
      started_at: new Date().toISOString(),
      completed_at: null,
    },
    questions: [
      {
        id: 10,
        position: 1,
        type: 'single',
        difficulty: 'easy',
        subject: { id: 1, name: 'Biology', slug: 'biology', color: '#16a34a' },
        text: 'Q1',
        image_url: null,
        answers: [
          { id: 100, text: 'A' },
          { id: 101, text: 'B' },
        ],
        selected_answer_ids: [],
        is_answered: false,
      },
      {
        id: 11,
        position: 2,
        type: 'multiple',
        difficulty: 'medium',
        subject: { id: 2, name: 'Chemistry', slug: 'chemistry', color: '#9333ea' },
        text: 'Q2',
        image_url: null,
        answers: [
          { id: 110, text: 'A' },
          { id: 111, text: 'B' },
        ],
        selected_answer_ids: [],
        is_answered: false,
      },
    ],
  }
}

describe('quiz store', () => {
  beforeEach(() => setActivePinia(createPinia()))
  afterEach(() => useQuizStore().reset())

  it('loads a payload and computes progress', () => {
    const store = useQuizStore()
    store.loadFromPayload(payload())

    expect(store.total).toBe(2)
    expect(store.answeredCount).toBe(0)
    expect(store.progress).toBe(0)
    expect(store.currentQuestion?.id).toBe(10)
  })

  it('replaces the selection for single-choice questions', () => {
    const store = useQuizStore()
    store.loadFromPayload(payload())
    const q1 = store.questions[0]!

    store.selectAnswer(q1, 100)
    expect(store.selections[10]).toEqual([100])
    expect(store.isAnswered(10)).toBe(true)
    expect(store.answeredCount).toBe(1)
    expect(store.progress).toBe(50)

    store.selectAnswer(q1, 101)
    expect(store.selections[10]).toEqual([101])
  })

  it('toggles multiple selections for multiple-choice questions', () => {
    const store = useQuizStore()
    store.loadFromPayload(payload())
    const q2 = store.questions[1]!

    store.selectAnswer(q2, 110)
    store.selectAnswer(q2, 111)
    expect(store.selections[11]).toEqual([110, 111])

    store.selectAnswer(q2, 110)
    expect(store.selections[11]).toEqual([111])
  })

  it('navigates between questions within bounds', () => {
    const store = useQuizStore()
    store.loadFromPayload(payload())

    expect(store.currentIndex).toBe(0)
    store.prev()
    expect(store.currentIndex).toBe(0)
    store.next()
    expect(store.currentIndex).toBe(1)
    store.next()
    expect(store.currentIndex).toBe(1)
  })
})
