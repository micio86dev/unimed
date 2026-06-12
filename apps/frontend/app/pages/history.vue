<script setup lang="ts">
import { ChevronRight, History as HistoryIcon } from 'lucide-vue-next'
import type { AttemptSummary, Paginated } from '~/types'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
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
    <PageHeader :title="$t('history.title')" :description="$t('history.subtitle')" />

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4">
          <Skeleton v-for="i in 6" :key="i" class="h-16" />
        </div>

        <EmptyState
          v-else-if="!data?.data.length"
          :icon="HistoryIcon"
          :title="$t('history.noHistory')"
          :description="$t('history.noHistoryDesc')"
          class="m-6"
        >
          <Button @click="navigateTo('/quizzes')">{{ $t('history.browseQuizzes') }}</Button>
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
              <p class="truncate font-medium">{{ $tf(a.quiz, 'title') || $t('history.quiz') }}</p>
              <p class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                <span>{{ formatDate(a.completed_at) }}</span>
                <span>·</span>
                <span>{{ a.correct_count }}/{{ a.total_questions }} {{ $t('history.correctSuffix') }}</span>
                <span class="hidden sm:inline">·</span>
                <span class="hidden sm:inline">{{ formatDuration(a.time_spent_seconds) }}</span>
              </p>
            </div>
            <Badge variant="muted" class="hidden sm:inline-flex">{{ a.points }} {{ $t('history.pts') }}</Badge>
            <ChevronRight class="size-4 text-muted-foreground" />
          </li>
        </ul>
      </CardContent>
    </Card>

    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-muted-foreground">{{ $t('history.pageOf', { current: data.meta.current_page, total: data.meta.last_page }) }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">{{ $t('common.previous') }}</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">{{ $t('common.next') }}</Button>
      </div>
    </div>
  </div>
</template>
