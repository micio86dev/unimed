<script setup lang="ts">
import { Clock, ListChecks, Plus, Trash2, Wand2 } from 'lucide-vue-next'
import type { Paginated, Quiz, Subject } from '~/types'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const { t } = useI18n()
const { success, error: toastError } = useToast()

const page = ref(1)
const query = computed(() => ({ scope: 'all', page: page.value, per_page: 12 }))

const { data: subjectsRes } = await useAsyncData('admin-quiz-subjects', () =>
  useApi()<{ data: Subject[] }>('/subjects'),
)
const subjects = computed(() => subjectsRes.value?.data ?? [])

const { data, pending, refresh } = await useAsyncData(
  'admin-quizzes',
  () => useApi()<Paginated<Quiz>>('/quizzes', { query: query.value }),
  { watch: [query] },
)

const modalOpen = ref(false)
const deleteTarget = ref<Quiz | null>(null)
const deleting = ref(false)
const togglingId = ref<number | null>(null)

async function togglePublish(quiz: Quiz) {
  togglingId.value = quiz.id
  try {
    await useApi()(`/admin/quizzes/${quiz.slug}`, {
      method: 'PATCH',
      body: { is_published: !quiz.is_published },
    })
    success(quiz.is_published ? t('admin.quizUnpublished') : t('admin.quizPublished'))
    await refresh()
  } catch (e) {
    toastError(t('admin.couldNotUpdate'), apiErrorMessage(e))
  } finally {
    togglingId.value = null
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await useApi()(`/admin/quizzes/${deleteTarget.value.slug}`, { method: 'DELETE' })
    success(t('admin.quizDeleted'))
    deleteTarget.value = null
    await refresh()
  } catch (e) {
    toastError(t('admin.couldNotDelete'), apiErrorMessage(e))
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="$t('admin.quizzesTitle')" :description="$t('admin.quizzesSubtitle')">
      <template #actions>
        <Button @click="modalOpen = true"><Plus class="size-4" /> {{ $t('admin.newQuiz') }}</Button>
      </template>
    </PageHeader>

    <div v-if="pending" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Skeleton v-for="i in 6" :key="i" class="h-40 rounded-xl" />
    </div>

    <EmptyState v-else-if="!data?.data.length" :icon="ListChecks" :title="$t('admin.noQuizzes')" :description="$t('admin.noQuizzesDesc')" class="py-16">
      <Button @click="modalOpen = true"><Plus class="size-4" /> {{ $t('admin.newQuiz') }}</Button>
    </EmptyState>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Card v-for="quiz in data.data" :key="quiz.id" class="flex flex-col">
        <CardHeader class="flex-1 pb-3">
          <div class="mb-1 flex items-center gap-2">
            <Badge :variant="quiz.is_published ? 'success' : 'muted'">{{ quiz.is_published ? $t('admin.published') : $t('admin.draft') }}</Badge>
            <Badge v-if="quiz.is_auto_generated" variant="outline"><Wand2 class="size-3" /> Auto</Badge>
          </div>
          <CardTitle class="line-clamp-1">{{ $tf(quiz, 'title') }}</CardTitle>
          <CardDescription class="line-clamp-2">{{ $tf(quiz, 'description') }}</CardDescription>
        </CardHeader>
        <CardContent class="pb-3">
          <div class="flex items-center gap-4 text-xs text-muted-foreground">
            <span class="flex items-center gap-1"><ListChecks class="size-3.5" /> {{ quiz.question_count }} Q</span>
            <span v-if="quiz.time_limit_minutes" class="flex items-center gap-1"><Clock class="size-3.5" /> {{ quiz.time_limit_minutes }}m</span>
            <span>{{ quiz.attempts_count ?? 0 }} {{ $t('admin.attempts') }}</span>
          </div>
        </CardContent>
        <CardFooter class="gap-2 border-t border-border pt-3">
          <Button variant="outline" size="sm" class="flex-1" :loading="togglingId === quiz.id" @click="togglePublish(quiz)">
            {{ quiz.is_published ? $t('admin.unpublish') : $t('admin.publish') }}
          </Button>
          <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-destructive" @click="deleteTarget = quiz">
            <Trash2 class="size-4" />
          </Button>
        </CardFooter>
      </Card>
    </div>

    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-muted-foreground">{{ $t('admin.pageOf', { current: data.meta.current_page, pages: data.meta.last_page }) }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">{{ $t('common.previous') }}</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">{{ $t('common.next') }}</Button>
      </div>
    </div>

    <AdminQuizFormModal v-model:open="modalOpen" :subjects="subjects" @saved="refresh" />

    <Modal :open="!!deleteTarget" :title="$t('admin.deleteQuizTitle')" :description="$t('admin.deleteQuizDesc')" @update:open="(v) => !v && (deleteTarget = null)">
      <p class="rounded-lg bg-muted/50 p-3 text-sm font-medium">{{ $tf(deleteTarget, 'title') }}</p>
      <div class="mt-5 flex justify-end gap-2">
        <Button variant="outline" @click="deleteTarget = null">{{ $t('admin.cancel') }}</Button>
        <Button variant="destructive" :loading="deleting" @click="confirmDelete">{{ $t('admin.delete') }}</Button>
      </div>
    </Modal>
  </div>
</template>
