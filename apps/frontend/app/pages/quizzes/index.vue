<script setup lang="ts">
import { Clock, ListChecks, Search, Sparkles, Wand2 } from 'lucide-vue-next'
import type { Paginated, Quiz } from '~/types'

definePageMeta({ middleware: 'auth' })

const search = ref('')
const difficulty = ref('')
const debouncedSearch = refDebounced(search, 300)

const query = computed(() => ({
  search: debouncedSearch.value || undefined,
  difficulty: difficulty.value || undefined,
  per_page: 24,
}))

const { data, pending } = await useAsyncData(
  'quiz-catalogue',
  () => useApi()<Paginated<Quiz>>('/quizzes', { query: query.value }),
  { watch: [query] },
)

const difficultyOptions = [
  { label: 'All difficulties', value: '' },
  { label: 'Easy', value: 'easy' },
  { label: 'Medium', value: 'medium' },
  { label: 'Hard', value: 'hard' },
]
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Quiz library" description="Pick a simulation and start practising." />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="search" placeholder="Search quizzes…" class="pl-9" />
      </div>
      <Select v-model="difficulty" :options="difficultyOptions" class="sm:w-48" />
    </div>

    <div v-if="pending" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Skeleton v-for="i in 6" :key="i" class="h-44 rounded-xl" />
    </div>

    <EmptyState
      v-else-if="!data?.data.length"
      :icon="Sparkles"
      title="No quizzes found"
      description="Try adjusting your search or filters."
    />

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Card
        v-for="quiz in data.data"
        :key="quiz.id"
        hover
        class="flex cursor-pointer flex-col"
        @click="navigateTo(`/quizzes/${quiz.slug}`)"
      >
        <CardHeader class="flex-1">
          <div class="mb-1 flex items-center gap-2">
            <DifficultyBadge v-if="quiz.difficulty" :difficulty="quiz.difficulty" />
            <Badge v-if="quiz.is_auto_generated" variant="muted">
              <Wand2 class="size-3" /> Auto
            </Badge>
          </div>
          <CardTitle class="line-clamp-1">{{ quiz.title }}</CardTitle>
          <CardDescription class="line-clamp-2">{{ quiz.description }}</CardDescription>
        </CardHeader>
        <CardFooter class="justify-between">
          <div class="flex items-center gap-4 text-xs text-muted-foreground">
            <span class="flex items-center gap-1"><ListChecks class="size-3.5" /> {{ quiz.question_count }} Q</span>
            <span v-if="quiz.time_limit_minutes" class="flex items-center gap-1">
              <Clock class="size-3.5" /> {{ quiz.time_limit_minutes }} min
            </span>
          </div>
          <Button size="sm" variant="secondary">Start</Button>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>
