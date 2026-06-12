<script setup lang="ts">
import { ArrowRight, CheckCircle2, Clock, Sparkles, Target, Trophy } from 'lucide-vue-next'
import type { StudentAnalytics } from '~/types'

definePageMeta({ middleware: 'auth' })

const auth = useAuthStore()
const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? 'there')

const { data, pending } = await useAsyncData('student-analytics', () =>
  useApi()<{ data: StudentAnalytics }>('/analytics/student').then((r) => r.data),
)

const subjectBars = computed(() =>
  (data.value?.subject_performance ?? []).map((s) => ({
    label: s.subject,
    value: s.accuracy,
    color: s.color,
    meta: `${s.correct}/${s.total}`,
  })),
)
const trendValues = computed(() => (data.value?.score_trend ?? []).map((p) => p.percentage))
</script>

<template>
  <div class="space-y-8">
    <PageHeader :title="`Good to see you, ${firstName} 👋`" description="Here's how your preparation is going.">
      <template #actions>
        <Button @click="navigateTo('/quizzes')">
          Browse quizzes <ArrowRight class="size-4" />
        </Button>
      </template>
    </PageHeader>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <template v-if="pending">
        <Skeleton v-for="i in 4" :key="i" class="h-[108px] rounded-xl" />
      </template>
      <template v-else>
        <StatCard label="Completed quizzes" :value="data?.kpis.completed_quizzes ?? 0" :icon="CheckCircle2" tone="primary" />
        <StatCard label="Average score" :value="formatPercent(data?.kpis.average_score, 1)" :icon="Target" tone="success" />
        <StatCard label="Best score" :value="formatPercent(data?.kpis.best_score, 1)" :icon="Trophy" tone="warning" />
        <StatCard label="Avg. completion time" :value="formatDuration(data?.kpis.average_time_seconds)" :icon="Clock" tone="primary" />
      </template>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <!-- Subject performance -->
      <Card class="lg:col-span-3">
        <CardHeader>
          <CardTitle>Performance by subject</CardTitle>
          <CardDescription>Accuracy across all your completed attempts.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="pending" class="space-y-4">
            <Skeleton v-for="i in 5" :key="i" class="h-8" />
          </div>
          <ChartsBarChart v-else-if="subjectBars.some((b) => b.value > 0)" :items="subjectBars" />
          <EmptyState
            v-else
            :icon="Sparkles"
            title="No data yet"
            description="Complete a quiz to see your subject performance."
          />
        </CardContent>
      </Card>

      <!-- Score trend + points -->
      <div class="space-y-6 lg:col-span-2">
        <Card>
          <CardHeader>
            <CardTitle>Score trend</CardTitle>
            <CardDescription>Your last attempts.</CardDescription>
          </CardHeader>
          <CardContent>
            <ChartsLineChart v-if="trendValues.length" :values="trendValues" :height="80" />
            <p v-else class="py-6 text-center text-sm text-muted-foreground">Not enough data yet.</p>
          </CardContent>
        </Card>
        <Card class="bg-primary text-primary-foreground">
          <CardContent class="flex items-center gap-4 p-6">
            <div class="flex size-12 items-center justify-center rounded-xl bg-white/15">
              <Sparkles class="size-6" />
            </div>
            <div>
              <p class="text-sm text-primary-foreground/80">Total points</p>
              <p class="text-2xl font-bold tabular-nums">{{ data?.kpis.total_points ?? 0 }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Recent activity -->
    <Card>
      <CardHeader class="flex-row items-center justify-between">
        <div>
          <CardTitle>Recent activity</CardTitle>
          <CardDescription>Your latest quiz attempts.</CardDescription>
        </div>
        <Button variant="ghost" size="sm" @click="navigateTo('/history')">View all</Button>
      </CardHeader>
      <CardContent>
        <div v-if="pending" class="space-y-3">
          <Skeleton v-for="i in 3" :key="i" class="h-14" />
        </div>
        <EmptyState
          v-else-if="!data?.recent_attempts.length"
          :icon="CheckCircle2"
          title="No attempts yet"
          description="Start your first quiz to track your progress."
        >
          <Button @click="navigateTo('/quizzes')">Start a quiz</Button>
        </EmptyState>
        <ul v-else class="divide-y divide-border">
          <li
            v-for="a in data.recent_attempts"
            :key="a.id"
            class="flex cursor-pointer items-center gap-4 py-3 transition-colors hover:bg-muted/40"
            @click="navigateTo(`/results/${a.id}`)"
          >
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
              <Trophy class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ a.quiz }}</p>
              <p class="text-xs text-muted-foreground">{{ formatRelative(a.completed_at) }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold tabular-nums" :class="scoreTextClass(a.percentage)">
                {{ formatPercent(a.percentage, 0) }}
              </p>
              <p class="text-xs text-muted-foreground">{{ a.correct_count }}/{{ a.total_questions }}</p>
            </div>
          </li>
        </ul>
      </CardContent>
    </Card>
  </div>
</template>
