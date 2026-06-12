<script setup lang="ts">
import { ChevronRight, History as HistoryIcon } from 'lucide-vue-next'
import type { AttemptSummary, Paginated } from '~/types'

definePageMeta({ middleware: 'auth' })

const page = ref(1)
const { data, pending } = await useAsyncData(
  'attempt-history',
  () =>
    useApi()<Paginated<AttemptSummary>>('/attempts', {
      query: { status: 'completed', page: page.value, per_page: 12 },
    }),
  { watch: [page] },
)
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="My attempts" description="Revisit every quiz you've completed." />

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4">
          <Skeleton v-for="i in 6" :key="i" class="h-16" />
        </div>

        <EmptyState
          v-else-if="!data?.data.length"
          :icon="HistoryIcon"
          title="No attempts yet"
          description="Once you complete a quiz, it will appear here."
          class="m-6"
        >
          <Button @click="navigateTo('/quizzes')">Browse quizzes</Button>
        </EmptyState>

        <ul v-else class="divide-y divide-border">
          <li
            v-for="a in data.data"
            :key="a.id"
            class="flex cursor-pointer items-center gap-4 px-4 py-4 transition-colors hover:bg-muted/40 sm:px-6"
            @click="navigateTo(`/results/${a.id}`)"
          >
            <div class="flex size-11 shrink-0 flex-col items-center justify-center rounded-lg border border-border">
              <span class="text-sm font-bold tabular-nums" :class="scoreTextClass(a.percentage)">{{ Math.round(a.percentage) }}</span>
              <span class="text-[10px] text-muted-foreground">%</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium">{{ a.quiz?.title ?? 'Quiz' }}</p>
              <p class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                <span>{{ formatDate(a.completed_at) }}</span>
                <span>·</span>
                <span>{{ a.correct_count }}/{{ a.total_questions }} correct</span>
                <span class="hidden sm:inline">·</span>
                <span class="hidden sm:inline">{{ formatDuration(a.time_spent_seconds) }}</span>
              </p>
            </div>
            <Badge variant="muted" class="hidden sm:inline-flex">{{ a.points }} pts</Badge>
            <ChevronRight class="size-4 text-muted-foreground" />
          </li>
        </ul>
      </CardContent>
    </Card>

    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-muted-foreground">Page {{ data.meta.current_page }} of {{ data.meta.last_page }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">Previous</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">Next</Button>
      </div>
    </div>
  </div>
</template>
