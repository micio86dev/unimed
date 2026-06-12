<script setup lang="ts">
import { Check, CircleHelp, Clock, RotateCcw, Sparkles, Trophy, X } from 'lucide-vue-next'
import { scoreTone } from '~/lib/format'
import type { ResultPayload } from '~/types'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const { t, tf } = useI18n()
const filter = ref<'all' | 'incorrect'>('all')

const { data, pending, error } = await useAsyncData(`result-${route.params.id}`, () =>
  useApi()<{ data: ResultPayload }>(`/attempts/${route.params.id}/result`).then((r) => r.data),
)

const subjectBars = computed(() =>
  (data.value?.subject_breakdown ?? []).map((s) => ({
    label: tf(s, 'subject'),
    value: s.accuracy,
    color: s.color,
    meta: `${s.correct}/${s.total}`,
  })),
)

const visibleReview = computed(() => {
  const review = data.value?.review ?? []
  return filter.value === 'incorrect' ? review.filter((r) => !r.is_correct) : review
})

const headline = computed(() => {
  const pct = data.value?.attempt.percentage ?? 0
  return pct >= 70
    ? t('results.greatWork')
    : pct >= 50
      ? t('results.goodEffort')
      : t('results.keepPractising')
})

const ringColor = computed(() => {
  const tone = scoreTone(data.value?.attempt.percentage)
  return tone === 'success' ? '#16a34a' : tone === 'warning' ? '#f59e0b' : '#e5484d'
})

function optionState(item: ResultPayload['review'][number], optionId: number) {
  const isCorrect = item.correct_answer_ids.includes(optionId)
  const isSelected = item.selected_answer_ids.includes(optionId)
  if (isCorrect) return 'correct'
  if (isSelected && !isCorrect) return 'wrong'
  return 'neutral'
}
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <div v-if="pending" class="space-y-4">
      <Skeleton class="h-64 rounded-xl" />
      <Skeleton class="h-40 rounded-xl" />
    </div>

    <EmptyState v-else-if="error || !data" :icon="X" :title="$t('results.notAvailable')" :description="$t('results.notAvailableDesc')">
      <Button @click="navigateTo('/dashboard')">{{ $t('results.backToDashboard') }}</Button>
    </EmptyState>

    <template v-else>
      <!-- Hero -->
      <Card class="overflow-hidden">
        <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-[auto_1fr] md:items-center">
          <div class="flex justify-center">
            <ChartsRadialScore :value="data.attempt.percentage" :color="ringColor" :label="$t('results.score')" />
          </div>
          <div class="space-y-4">
            <div>
              <p class="text-sm font-medium text-muted-foreground">{{ $tf(data.attempt.quiz, 'title') }}</p>
              <h1 class="text-2xl font-semibold tracking-tight">{{ headline }}</h1>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div class="rounded-lg border border-border p-3">
                <p class="flex items-center gap-1 text-xs text-muted-foreground"><Check class="size-3.5 text-success" /> {{ $t('results.correct') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ data.attempt.correct_count }}</p>
              </div>
              <div class="rounded-lg border border-border p-3">
                <p class="flex items-center gap-1 text-xs text-muted-foreground"><X class="size-3.5 text-destructive" /> {{ $t('results.wrong') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ data.attempt.incorrect_count }}</p>
              </div>
              <div class="rounded-lg border border-border p-3">
                <p class="flex items-center gap-1 text-xs text-muted-foreground"><CircleHelp class="size-3.5" /> {{ $t('results.skipped') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ data.attempt.unanswered_count }}</p>
              </div>
              <div class="rounded-lg border border-border p-3">
                <p class="flex items-center gap-1 text-xs text-muted-foreground"><Clock class="size-3.5" /> {{ $t('results.time') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ formatDuration(data.attempt.time_spent_seconds) }}</p>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button variant="outline" @click="navigateTo('/dashboard')">{{ $t('results.dashboard') }}</Button>
              <Button variant="outline" @click="navigateTo('/rankings')">
                <Trophy class="size-4" /> {{ $t('results.rankings') }}
              </Button>
              <Button v-if="data.attempt.quiz?.slug" @click="navigateTo(`/quizzes/${data.attempt.quiz.slug}`)">
                <RotateCcw class="size-4" /> {{ $t('results.tryAgain') }}
              </Button>
            </div>
          </div>
        </div>
      </Card>

      <!-- Subject breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>{{ $t('results.performanceBySubject') }}</CardTitle>
          <CardDescription>{{ $t('results.performanceBySubjectDesc') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <ChartsBarChart :items="subjectBars" />
        </CardContent>
      </Card>

      <!-- Review -->
      <Card>
        <CardHeader class="flex-row items-center justify-between">
          <div>
            <CardTitle>{{ $t('results.answerReview') }}</CardTitle>
            <CardDescription>{{ $t('results.answerReviewDesc') }}</CardDescription>
          </div>
          <div class="flex rounded-lg border border-border p-0.5">
            <button
              v-for="opt in (['all', 'incorrect'] as const)"
              :key="opt"
              class="cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-colors"
              :class="filter === opt ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
              @click="filter = opt"
            >
              {{ opt === 'all' ? $t('results.filterAll') : $t('results.filterIncorrect') }}
            </button>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <EmptyState v-if="!visibleReview.length" :icon="Sparkles" :title="$t('results.nothingToReview')" :description="$t('results.nothingToReviewDesc')" />
          <div v-for="item in visibleReview" :key="item.position" class="rounded-xl border border-border p-4 sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
              <div class="flex items-center gap-2">
                <span
                  class="flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                  :class="item.is_correct ? 'bg-success/15 text-success' : item.is_answered ? 'bg-destructive/15 text-destructive' : 'bg-muted text-muted-foreground'"
                >
                  <Check v-if="item.is_correct" class="size-3.5" />
                  <X v-else-if="item.is_answered" class="size-3.5" />
                  <template v-else>?</template>
                </span>
                <span class="text-xs font-medium text-muted-foreground">Q{{ item.position }} · {{ $tf(item.question.subject, 'name') }}</span>
              </div>
              <DifficultyBadge :difficulty="item.question.difficulty" />
            </div>

            <p class="font-medium">{{ $tf(item.question, 'text') }}</p>

            <div class="mt-3 space-y-2">
              <div
                v-for="opt in item.options"
                :key="opt.id"
                class="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-sm"
                :class="{
                  'border-success/40 bg-success/8 text-success-foreground': optionState(item, opt.id) === 'correct',
                  'border-destructive/40 bg-destructive/8': optionState(item, opt.id) === 'wrong',
                  'border-border': optionState(item, opt.id) === 'neutral',
                }"
              >
                <Check v-if="optionState(item, opt.id) === 'correct'" class="size-4 shrink-0 text-success" />
                <X v-else-if="optionState(item, opt.id) === 'wrong'" class="size-4 shrink-0 text-destructive" />
                <span v-else class="size-4 shrink-0 rounded-full border border-border" />
                <span :class="optionState(item, opt.id) === 'correct' ? 'font-medium text-foreground' : 'text-foreground'">{{ $tf(opt, 'text') }}</span>
              </div>
            </div>

            <div v-if="$tf(item.question, 'explanation')" class="mt-3 rounded-lg bg-secondary/60 p-3 text-sm">
              <span class="font-semibold text-secondary-foreground">{{ $t('results.explanation') }}: </span>
              <span class="text-foreground/80">{{ $tf(item.question, 'explanation') }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
