<script setup lang="ts">
import { ArrowLeft, Clock, Hourglass, ListChecks, Play, ShieldCheck } from 'lucide-vue-next'
import type { Quiz } from '~/types'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const quizStore = useQuizStore()
const { t } = useI18n()
const { error: toastError } = useToast()
const starting = ref(false)

const { data, pending, error } = await useAsyncData(`quiz-${route.params.slug}`, () =>
  useApi()<{ data: Quiz }>(`/quizzes/${route.params.slug}`).then((r) => r.data),
)

async function start() {
  if (!data.value) return
  starting.value = true
  try {
    const attemptId = await quizStore.start(data.value.id)
    await navigateTo(`/attempt/${attemptId}`)
  } catch (e) {
    toastError(t('quizzes.couldNotStart'), apiErrorMessage(e))
    starting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <NuxtLink to="/quizzes" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
      <ArrowLeft class="size-4" /> {{ $t('quizzes.backToLibrary') }}
    </NuxtLink>

    <div v-if="pending"><Skeleton class="h-72 rounded-xl" /></div>

    <EmptyState v-else-if="error || !data" :icon="ListChecks" :title="$t('quizzes.notFound')" :description="$t('quizzes.notFoundDesc')" />

    <Card v-else>
      <CardHeader class="border-b border-border">
        <div class="mb-2 flex items-center gap-2">
          <DifficultyBadge v-if="data.difficulty" :difficulty="data.difficulty" />
          <Badge variant="muted">{{ data.question_count }} {{ $t('quizzes.questions') }}</Badge>
        </div>
        <CardTitle class="text-2xl">{{ $tf(data, 'title') }}</CardTitle>
        <CardDescription class="text-base">{{ $tf(data, 'description') }}</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="flex items-center gap-3 rounded-lg border border-border p-4">
            <ListChecks class="size-5 text-primary" />
            <div>
              <p class="text-sm font-semibold">{{ data.question_count }}</p>
              <p class="text-xs text-muted-foreground">{{ $t('quizzes.questions') }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 rounded-lg border border-border p-4">
            <Clock class="size-5 text-primary" />
            <div>
              <p class="text-sm font-semibold">{{ data.time_limit_minutes ? `${data.time_limit_minutes} ${$t('quizzes.minutes')}` : $t('quizzes.untimed') }}</p>
              <p class="text-xs text-muted-foreground">{{ $t('quizzes.timeLimit') }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 rounded-lg border border-border p-4">
            <ShieldCheck class="size-5 text-primary" />
            <div>
              <p class="text-sm font-semibold">{{ $t('quizzes.autosaved') }}</p>
              <p class="text-xs text-muted-foreground">{{ $t('quizzes.resumeAnytime') }}</p>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-lg bg-muted/50 p-4">
          <h3 class="flex items-center gap-2 text-sm font-semibold">
            <Hourglass class="size-4 text-muted-foreground" /> {{ $t('quizzes.beforeYouBegin') }}
          </h3>
          <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
            <li>• {{ $t('quizzes.tip1') }}</li>
            <li>• {{ $t('quizzes.tip2') }}</li>
            <li v-if="data.time_limit_minutes">• {{ $t('quizzes.tip3') }}</li>
            <li>• {{ $t('quizzes.tip4') }}</li>
          </ul>
        </div>
      </CardContent>
      <CardFooter>
        <Button size="lg" class="w-full" :loading="starting" @click="start">
          <Play class="size-4" /> {{ $t('quizzes.startSimulation') }}
        </Button>
      </CardFooter>
    </Card>
  </div>
</template>
