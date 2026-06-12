<script setup lang="ts">
import { CircleHelp, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next'
import type { Paginated, Question, Subject } from '~/types'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const { success, error: toastError } = useToast()

const search = ref('')
const subjectId = ref('')
const difficulty = ref('')
const page = ref(1)
const debouncedSearch = refDebounced(search, 300)

watch([debouncedSearch, subjectId, difficulty], () => {
  page.value = 1
})

const query = computed(() => ({
  search: debouncedSearch.value || undefined,
  subject_id: subjectId.value || undefined,
  difficulty: difficulty.value || undefined,
  page: page.value,
  per_page: 12,
}))

const { data: subjectsRes } = await useAsyncData('admin-subjects', () =>
  useApi()<{ data: Subject[] }>('/subjects'),
)
const subjects = computed(() => subjectsRes.value?.data ?? [])

const { data, pending, refresh } = await useAsyncData(
  'admin-questions',
  () => useApi()<Paginated<Question>>('/admin/questions', { query: query.value }),
  { watch: [query] },
)

const modalOpen = ref(false)
const editing = ref<Question | null>(null)
const deleteTarget = ref<Question | null>(null)
const deleting = ref(false)

function openCreate() {
  editing.value = null
  modalOpen.value = true
}
function openEdit(q: Question) {
  editing.value = q
  modalOpen.value = true
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await useApi()(`/admin/questions/${deleteTarget.value.id}`, { method: 'DELETE' })
    success('Question deleted')
    deleteTarget.value = null
    await refresh()
  } catch (e) {
    toastError('Could not delete', apiErrorMessage(e))
  } finally {
    deleting.value = false
  }
}

const subjectOptions = computed(() => [
  { label: 'All subjects', value: '' },
  ...subjects.value.map((s) => ({ label: s.name, value: String(s.id) })),
])
const difficultyOptions = [
  { label: 'All difficulties', value: '' },
  { label: 'Easy', value: 'easy' },
  { label: 'Medium', value: 'medium' },
  { label: 'Hard', value: 'hard' },
]
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Questions" description="Manage the question bank.">
      <template #actions>
        <Button @click="openCreate"><Plus class="size-4" /> New question</Button>
      </template>
    </PageHeader>

    <div class="flex flex-col gap-3 sm:flex-row">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="search" placeholder="Search questions…" class="pl-9" />
      </div>
      <Select v-model="subjectId" :options="subjectOptions" class="sm:w-44" />
      <Select v-model="difficulty" :options="difficultyOptions" class="sm:w-44" />
    </div>

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4"><Skeleton v-for="i in 8" :key="i" class="h-14" /></div>
        <EmptyState v-else-if="!data?.data.length" :icon="CircleHelp" title="No questions found" description="Try adjusting filters or create a new one." class="m-6" />
        <ul v-else class="divide-y divide-border">
          <li v-for="q in data.data" :key="q.id" class="flex items-start gap-4 px-4 py-3.5 sm:px-6">
            <SubjectIcon :slug="q.subject?.slug" :color="q.subject?.color" size="sm" class="mt-0.5" />
            <div class="min-w-0 flex-1">
              <p class="line-clamp-2 text-sm font-medium">{{ q.text }}</p>
              <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <Badge variant="secondary">{{ q.subject?.name }}</Badge>
                <DifficultyBadge :difficulty="q.difficulty" />
                <Badge variant="muted">{{ q.type === 'single' ? 'Single' : 'Multiple' }}</Badge>
                <Badge v-if="!q.is_active" variant="outline">Inactive</Badge>
              </div>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <button class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground" @click="openEdit(q)">
                <Pencil class="size-4" />
              </button>
              <button class="rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive" @click="deleteTarget = q">
                <Trash2 class="size-4" />
              </button>
            </div>
          </li>
        </ul>
      </CardContent>
    </Card>

    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-muted-foreground">{{ data.meta.total }} questions · page {{ data.meta.current_page }} of {{ data.meta.last_page }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">Previous</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">Next</Button>
      </div>
    </div>

    <AdminQuestionFormModal v-model:open="modalOpen" :question="editing" :subjects="subjects" @saved="refresh" />

    <Modal :open="!!deleteTarget" title="Delete question?" description="This action cannot be undone." @update:open="(v) => !v && (deleteTarget = null)">
      <p class="line-clamp-3 rounded-lg bg-muted/50 p-3 text-sm">{{ deleteTarget?.text }}</p>
      <div class="mt-5 flex justify-end gap-2">
        <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
        <Button variant="destructive" :loading="deleting" @click="confirmDelete">Delete</Button>
      </div>
    </Modal>
  </div>
</template>
