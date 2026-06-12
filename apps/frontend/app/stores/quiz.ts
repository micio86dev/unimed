import { defineStore } from 'pinia'
import type { AttemptQuestion, AttemptSummary, ResultPayload, TakePayload } from '~/types'

export const useQuizStore = defineStore('quiz', () => {
  const attempt = ref<AttemptSummary | null>(null)
  const questions = ref<AttemptQuestion[]>([])
  const currentIndex = ref(0)
  const selections = reactive<Record<number, number[]>>({})
  const savingIds = reactive<Set<number>>(new Set())
  const submitting = ref(false)
  const loading = ref(false)

  const elapsedSeconds = ref(0)
  const timeLimitSeconds = ref<number | null>(null)
  let timer: ReturnType<typeof setInterval> | null = null

  const currentQuestion = computed<AttemptQuestion | null>(
    () => questions.value[currentIndex.value] ?? null,
  )
  const total = computed(() => questions.value.length)

  const answeredCount = computed(
    () => questions.value.filter((q) => (selections[q.id]?.length ?? 0) > 0).length,
  )
  const remainingCount = computed(() => total.value - answeredCount.value)
  const progress = computed(() =>
    total.value === 0 ? 0 : Math.round((answeredCount.value / total.value) * 100),
  )

  const remainingSeconds = computed(() => {
    if (timeLimitSeconds.value == null) return null
    return Math.max(0, timeLimitSeconds.value - elapsedSeconds.value)
  })

  function isAnswered(questionId: number) {
    return (selections[questionId]?.length ?? 0) > 0
  }

  function loadFromPayload(payload: TakePayload) {
    attempt.value = payload.attempt
    questions.value = payload.questions
    currentIndex.value = 0
    for (const key of Object.keys(selections)) delete selections[Number(key)]
    for (const q of payload.questions) {
      selections[q.id] = [...(q.selected_answer_ids ?? [])]
    }
    timeLimitSeconds.value = payload.attempt.quiz?.time_limit_minutes
      ? payload.attempt.quiz.time_limit_minutes * 60
      : null

    // Resume: approximate elapsed time from the server start timestamp.
    const startedAt = payload.attempt.started_at
    elapsedSeconds.value = startedAt
      ? Math.max(0, Math.floor((Date.now() - new Date(startedAt).getTime()) / 1000))
      : 0
    startTimer()
  }

  async function start(quizId: number) {
    loading.value = true
    try {
      const api = useApi()
      const res = await api<{ data: TakePayload }>('/attempts', {
        method: 'POST',
        body: { quiz_id: quizId },
      })
      loadFromPayload(res.data)
      return res.data.attempt.id
    } finally {
      loading.value = false
    }
  }

  async function resume(attemptId: number) {
    loading.value = true
    try {
      const api = useApi()
      const res = await api<{ data: TakePayload }>(`/attempts/${attemptId}`)
      loadFromPayload(res.data)
    } finally {
      loading.value = false
    }
  }

  function selectAnswer(question: AttemptQuestion, answerId: number) {
    const current = selections[question.id] ?? []
    if (question.type === 'multiple') {
      selections[question.id] = current.includes(answerId)
        ? current.filter((id) => id !== answerId)
        : [...current, answerId]
    } else {
      selections[question.id] = current[0] === answerId ? [] : [answerId]
    }
    void saveAnswer(question.id)
  }

  async function saveAnswer(questionId: number) {
    if (!attempt.value) return
    savingIds.add(questionId)
    try {
      const api = useApi()
      await api(`/attempts/${attempt.value.id}/answers`, {
        method: 'PATCH',
        body: { question_id: questionId, selected_answer_ids: selections[questionId] ?? [] },
      })
    } catch {
      // Keep the local selection; it will be retried on the next change / submit.
    } finally {
      savingIds.delete(questionId)
    }
  }

  function goTo(index: number) {
    currentIndex.value = Math.min(Math.max(0, index), total.value - 1)
  }
  function next() {
    if (currentIndex.value < total.value - 1) currentIndex.value++
  }
  function prev() {
    if (currentIndex.value > 0) currentIndex.value--
  }

  async function submit(): Promise<ResultPayload> {
    if (!attempt.value) throw new Error('No active attempt')
    submitting.value = true
    stopTimer()
    try {
      const api = useApi()
      const res = await api<{ data: ResultPayload }>(`/attempts/${attempt.value.id}/submit`, {
        method: 'POST',
        body: { time_spent_seconds: elapsedSeconds.value },
      })
      return res.data
    } finally {
      submitting.value = false
    }
  }

  function startTimer() {
    if (!import.meta.client || timer) return
    timer = setInterval(() => {
      elapsedSeconds.value++
      if (remainingSeconds.value === 0 && timeLimitSeconds.value != null) {
        stopTimer()
      }
    }, 1000)
  }

  function stopTimer() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  function reset() {
    stopTimer()
    attempt.value = null
    questions.value = []
    currentIndex.value = 0
    elapsedSeconds.value = 0
    timeLimitSeconds.value = null
    for (const key of Object.keys(selections)) delete selections[Number(key)]
    savingIds.clear()
  }

  return {
    attempt,
    questions,
    currentIndex,
    selections,
    savingIds,
    submitting,
    loading,
    elapsedSeconds,
    timeLimitSeconds,
    remainingSeconds,
    currentQuestion,
    total,
    answeredCount,
    remainingCount,
    progress,
    isAnswered,
    start,
    resume,
    loadFromPayload,
    selectAnswer,
    saveAnswer,
    goTo,
    next,
    prev,
    submit,
    startTimer,
    stopTimer,
    reset,
  }
})
