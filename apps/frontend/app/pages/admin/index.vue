<script setup lang="ts">
import {
  Activity,
  BookOpen,
  CircleHelp,
  ListChecks,
  Target,
  UserCheck,
  Users,
} from 'lucide-vue-next'
import type { AdminAnalytics } from '~/types'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const { data, pending } = await useAsyncData('admin-analytics', () =>
  useApi()<{ data: AdminAnalytics }>('/admin/analytics').then((r) => r.data),
)

const hardestBars = computed(() =>
  (data.value?.hardest_subjects ?? []).map((s) => ({
    label: s.subject,
    value: s.accuracy,
    color: s.color,
    meta: `${s.answered} answered`,
  })),
)
const trend = computed(() => data.value?.attempts_trend ?? [])
const trendValues = computed(() => trend.value.map((t) => t.count))
const trendMax = computed(() => Math.max(5, ...trendValues.value))
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Admin overview" description="Platform health at a glance." />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <template v-if="pending">
        <Skeleton v-for="i in 4" :key="i" class="h-[108px] rounded-xl" />
      </template>
      <template v-else>
        <StatCard label="Total students" :value="data?.kpis.total_students ?? 0" :icon="Users" tone="primary" />
        <StatCard label="Active (30d)" :value="data?.kpis.active_users ?? 0" :icon="UserCheck" tone="success" hint="Completed a quiz recently" />
        <StatCard label="Completed quizzes" :value="data?.kpis.completed_quizzes ?? 0" :icon="ListChecks" tone="primary" />
        <StatCard label="Average score" :value="formatPercent(data?.kpis.average_score, 1)" :icon="Target" tone="warning" />
      </template>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <Card class="lg:col-span-3">
        <CardHeader>
          <CardTitle>Hardest subjects</CardTitle>
          <CardDescription>Lowest average accuracy across all students.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="pending" class="space-y-4"><Skeleton v-for="i in 5" :key="i" class="h-8" /></div>
          <ChartsBarChart v-else :items="hardestBars" />
        </CardContent>
      </Card>

      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Activity (14 days)</CardTitle>
          <CardDescription>Quizzes completed per day.</CardDescription>
        </CardHeader>
        <CardContent>
          <ChartsLineChart v-if="trendValues.length" :values="trendValues" :max="trendMax" :height="120" />
          <div class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
            <span>{{ trend[0]?.date }}</span>
            <span class="flex items-center gap-1"><Activity class="size-3.5" /> {{ trendValues.reduce((a, b) => a + b, 0) }} total</span>
            <span>{{ trend[trend.length - 1]?.date }}</span>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <Card hover class="cursor-pointer" @click="navigateTo('/admin/questions')">
        <CardContent class="flex items-center gap-4 p-5">
          <div class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"><CircleHelp class="size-5" /></div>
          <div>
            <p class="text-sm text-muted-foreground">Question bank</p>
            <p class="text-xl font-semibold tabular-nums">{{ data?.kpis.total_questions ?? 0 }}</p>
          </div>
        </CardContent>
      </Card>
      <Card hover class="cursor-pointer" @click="navigateTo('/admin/quizzes')">
        <CardContent class="flex items-center gap-4 p-5">
          <div class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"><ListChecks class="size-5" /></div>
          <div>
            <p class="text-sm text-muted-foreground">Quizzes</p>
            <p class="text-xl font-semibold tabular-nums">{{ data?.kpis.total_quizzes ?? 0 }}</p>
          </div>
        </CardContent>
      </Card>
      <Card hover class="cursor-pointer" @click="navigateTo('/admin/users')">
        <CardContent class="flex items-center gap-4 p-5">
          <div class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"><BookOpen class="size-5" /></div>
          <div>
            <p class="text-sm text-muted-foreground">Total users</p>
            <p class="text-xl font-semibold tabular-nums">{{ data?.kpis.total_users ?? 0 }}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
