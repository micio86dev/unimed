<script setup lang="ts">
import { Check, ChevronLeft, ChevronRight, Clock, Cloud, Flag, Loader2, X } from 'lucide-vue-next'
import type { ResultPayload } from '~/types'

definePageMeta({ layout: false, middleware: 'auth' })

const route = useRoute()
const store = useQuizStore()
const { error: toastError } = useToast()

const attemptId = Number(route.params.id)
const loadError = ref(false)
const confirmOpen = ref(false)

onMounted(async () => {
  if (store.attempt?.id !== attemptId) {
    try {
      await store.resume(attemptId)
    } catch (e) {
      // Completed attempts 409 → go straight to the result.
      if ((e as { response?: { status?: number } })?.response?.status === 409) {
        return navigateTo(`/results/${attemptId}`, { replace: true })
      }
      loadError.value = true
    }
  }
})

onBeforeUnmount(() => store.stopTimer())

const clock = computed(() => {
  const s = store.timeLimitSeconds != null ? (store.remainingSeconds ?? 0) : store.elapsedSeconds
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
})
const lowTime = computed(
  () => store.timeLimitSeconds != null && (store.remainingSeconds ?? 0) <= 60,
)

// Auto-submit when the clock runs out.
watch(
  () => store.remainingSeconds,
  (r) => {
    if (r === 0 && store.timeLimitSeconds != null && store.attempt) doSubmit(true)
  },
)

function selected(questionId: number, answerId: number) {
  return (store.selections[questionId] ?? []).includes(answerId)
}

async function doSubmit(auto = false) {
  confirmOpen.value = false
  try {
    const result: ResultPayload = await store.submit()
    const id = result.attempt.id
    store.reset()
    await navigateTo(`/results/${id}`, { replace: true })
  } catch (e) {
    if (!auto) toastError('Could not submit', apiErrorMessage(e))
  }
}

// Keyboard navigation.
function onKey(e: KeyboardEvent) {
  if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return
  if (e.key === 'ArrowRight') store.next()
  if (e.key === 'ArrowLeft') store.prev()
}
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <div class="flex min-h-screen flex-col bg-muted/30">
    <!-- Top bar -->
    <header class="sticky top-0 z-30 border-b border-border bg-background/90 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
        <button class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground" @click="navigateTo('/quizzes')">
          <X class="size-4" /> <span class="hidden sm:inline">Exit</span>
        </button>
        <p class="hidden truncate text-sm font-medium sm:block">{{ store.attempt?.quiz?.title }}</p>

        <div class="ml-auto flex items-center gap-3 sm:gap-5">
          <div class="hidden items-center gap-2 text-sm text-muted-foreground sm:flex">
            <Cloud v-if="store.savingIds.size === 0" class="size-4 text-success" />
            <Loader2 v-else class="size-4 animate-spin" />
            <span>{{ store.savingIds.size === 0 ? 'Saved' : 'Saving…' }}</span>
          </div>
          <div
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold tabular-nums"
            :class="lowTime ? 'bg-destructive/12 text-destructive' : 'bg-secondary text-secondary-foreground'"
          >
            <Clock class="size-4" /> {{ clock }}
          </div>
          <Button size="sm" @click="confirmOpen = true">
            <Flag class="size-4" /> <span class="hidden sm:inline">Submit</span>
          </Button>
        </div>
      </div>
      <Progress :value="store.progress" class="h-1 rounded-none" />
    </header>

    <div v-if="store.loading" class="flex flex-1 items-center justify-center">
      <Spinner class="size-8" />
    </div>
    <div v-else-if="loadError" class="flex flex-1 items-center justify-center">
      <EmptyState :icon="X" title="Could not load this attempt" description="It may have expired.">
        <Button @click="navigateTo('/quizzes')">Back to quizzes</Button>
      </EmptyState>
    </div>

    <div v-else-if="store.currentQuestion" class="mx-auto flex w-full max-w-6xl flex-1 gap-6 px-4 py-6 sm:px-6">
      <!-- Question -->
      <main class="flex-1">
        <div class="mb-4 flex items-center justify-between text-sm">
          <span class="font-medium text-muted-foreground">
            Question {{ store.currentIndex + 1 }} of {{ store.total }}
          </span>
          <span class="text-muted-foreground">{{ store.answeredCount }} answered · {{ store.remainingCount }} left</span>
        </div>

        <Card class="overflow-hidden">
          <CardContent class="p-6 sm:p-8">
            <div class="mb-4 flex items-center gap-2">
              <Badge variant="secondary" class="gap-1.5">
                <span class="size-2 rounded-full" :style="{ background: store.currentQuestion.subject.color }" />
                {{ store.currentQuestion.subject.name }}
              </Badge>
              <DifficultyBadge :difficulty="store.currentQuestion.difficulty" />
              <Badge v-if="store.currentQuestion.type === 'multiple'" variant="muted">Select all that apply</Badge>
            </div>

            <p class="text-lg font-medium leading-relaxed">{{ store.currentQuestion.text }}</p>
            <img
              v-if="store.currentQuestion.image_url"
              :src="store.currentQuestion.image_url"
              alt=""
              class="mt-4 max-h-72 rounded-lg border border-border"
            >

            <div class="mt-6 space-y-3">
              <button
                v-for="(answer, i) in store.currentQuestion.answers"
                :key="answer.id"
                type="button"
                data-testid="answer-option"
                class="group flex w-full items-center gap-3 rounded-xl border p-4 text-left transition-all"
                :class="selected(store.currentQuestion.id, answer.id)
                  ? 'border-primary bg-primary/5 ring-1 ring-primary'
                  : 'border-border hover:border-primary/40 hover:bg-muted/50'"
                @click="store.selectAnswer(store.currentQuestion, answer.id)"
              >
                <span
                  class="flex size-7 shrink-0 items-center justify-center text-xs font-semibold transition-colors"
                  :class="[
                    store.currentQuestion.type === 'multiple' ? 'rounded-md' : 'rounded-full',
                    selected(store.currentQuestion.id, answer.id)
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-muted text-muted-foreground group-hover:bg-secondary',
                  ]"
                >
                  <Check v-if="selected(store.currentQuestion.id, answer.id)" class="size-4" />
                  <template v-else>{{ String.fromCharCode(65 + i) }}</template>
                </span>
                <span class="text-sm">{{ answer.text }}</span>
              </button>
            </div>
          </CardContent>
        </Card>

        <div class="mt-5 flex items-center justify-between">
          <Button variant="outline" :disabled="store.currentIndex === 0" @click="store.prev()">
            <ChevronLeft class="size-4" /> Previous
          </Button>
          <Button
            v-if="store.currentIndex < store.total - 1"
            variant="outline"
            @click="store.next()"
          >
            Next <ChevronRight class="size-4" />
          </Button>
          <Button v-else @click="confirmOpen = true">
            <Flag class="size-4" /> Review & submit
          </Button>
        </div>
      </main>

      <!-- Navigator -->
      <aside class="hidden w-64 shrink-0 lg:block">
        <Card class="sticky top-24">
          <CardHeader class="pb-3">
            <CardTitle class="text-sm">Questions</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-5 gap-2">
              <button
                v-for="(q, i) in store.questions"
                :key="q.id"
                class="flex aspect-square items-center justify-center rounded-lg border text-xs font-semibold transition-colors"
                :class="[
                  i === store.currentIndex ? 'ring-2 ring-primary ring-offset-1 ring-offset-background' : '',
                  store.isAnswered(q.id)
                    ? 'border-transparent bg-primary text-primary-foreground'
                    : 'border-border bg-muted/50 text-muted-foreground hover:bg-muted',
                ]"
                @click="store.goTo(i)"
              >
                {{ i + 1 }}
              </button>
            </div>
            <div class="mt-4 space-y-1.5 text-xs text-muted-foreground">
              <p class="flex items-center gap-2"><span class="size-3 rounded bg-primary" /> Answered</p>
              <p class="flex items-center gap-2"><span class="size-3 rounded border border-border bg-muted/50" /> Unanswered</p>
            </div>
            <Button class="mt-4 w-full" @click="confirmOpen = true">Submit quiz</Button>
          </CardContent>
        </Card>
      </aside>
    </div>

    <!-- Submit confirmation -->
    <Modal v-model:open="confirmOpen" title="Submit your quiz?" description="You won't be able to change your answers afterwards.">
      <div class="grid grid-cols-3 gap-3 py-2">
        <div class="rounded-lg bg-muted/50 p-3 text-center">
          <p class="text-xl font-bold tabular-nums">{{ store.total }}</p>
          <p class="text-xs text-muted-foreground">Total</p>
        </div>
        <div class="rounded-lg bg-success/10 p-3 text-center">
          <p class="text-xl font-bold tabular-nums text-success">{{ store.answeredCount }}</p>
          <p class="text-xs text-muted-foreground">Answered</p>
        </div>
        <div class="rounded-lg bg-warning/10 p-3 text-center">
          <p class="text-xl font-bold tabular-nums text-warning">{{ store.remainingCount }}</p>
          <p class="text-xs text-muted-foreground">Unanswered</p>
        </div>
      </div>
      <p v-if="store.remainingCount > 0" class="text-sm text-muted-foreground">
        You still have {{ store.remainingCount }} unanswered question{{ store.remainingCount > 1 ? 's' : '' }}.
      </p>
      <div class="mt-5 flex justify-end gap-2">
        <Button variant="outline" @click="confirmOpen = false">Keep going</Button>
        <Button :loading="store.submitting" @click="doSubmit(false)">Submit quiz</Button>
      </div>
    </Modal>
  </div>
</template>
