<script setup lang="ts">
import { Check, Search, Wand2 } from 'lucide-vue-next'
import type { Paginated, Question, Subject } from '~/types'

const props = defineProps<{ open: boolean; subjects: Subject[] }>()
const emit = defineEmits<{ 'update:open': [value: boolean]; saved: [] }>()

const { t, tf } = useI18n()
const { success, error: toastError } = useToast()

const form = reactive({
  title: '',
  title_it: '',
  description: '',
  description_it: '',
  time_limit_minutes: 30 as number | null,
  difficulty: '' as '' | 'easy' | 'medium' | 'hard',
  is_published: true,
  mode: 'auto' as 'auto' | 'manual',
  question_count: 20,
  subject_ids: [] as number[],
})
const selectedIds = ref<number[]>([])
const search = ref('')
const debouncedSearch = refDebounced(search, 300)
const errors = ref<Record<string, string>>({})
const saving = ref(false)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    Object.assign(form, {
      title: '',
      title_it: '',
      description: '',
      description_it: '',
      time_limit_minutes: 30,
      difficulty: '',
      is_published: true,
      mode: 'auto',
      question_count: 20,
      subject_ids: [],
    })
    selectedIds.value = []
    search.value = ''
    errors.value = {}
  },
)

const { data: results } = await useAsyncData(
  'quiz-question-picker',
  () =>
    useApi()<Paginated<Question>>('/admin/questions', {
      query: { search: debouncedSearch.value || undefined, per_page: 15 },
    }),
  { watch: [debouncedSearch], immediate: false },
)
watch(
  () => form.mode,
  (m) => {
    if (m === 'manual') refreshNuxtData('quiz-question-picker')
  },
)

function toggleSubject(id: number) {
  form.subject_ids = form.subject_ids.includes(id)
    ? form.subject_ids.filter((s) => s !== id)
    : [...form.subject_ids, id]
}
function toggleQuestion(id: number) {
  selectedIds.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter((s) => s !== id)
    : [...selectedIds.value, id]
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const payload: Record<string, unknown> = {
      title: form.title,
      title_it: form.title_it || null,
      description: form.description || null,
      description_it: form.description_it || null,
      time_limit_minutes: form.time_limit_minutes || null,
      difficulty: form.difficulty || null,
      is_published: form.is_published,
      mode: form.mode,
    }
    if (form.mode === 'auto') {
      payload.question_count = form.question_count
      payload.subject_ids = form.subject_ids
    } else {
      payload.question_ids = selectedIds.value
    }
    await useApi()('/admin/quizzes', { method: 'POST', body: payload })
    success(t('admin.quizCreated'))
    emit('saved')
    emit('update:open', false)
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (!Object.keys(errors.value).length)
      toastError(t('admin.couldNotCreateQuiz'), apiErrorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal :open="open" class="max-w-2xl" @update:open="emit('update:open', $event)">
    <div class="mb-4">
      <h2 class="text-lg font-semibold tracking-tight">{{ $t('admin.newQuiz') }}</h2>
      <p class="text-sm text-muted-foreground">{{ $t('admin.quizModalSubtitle') }}</p>
    </div>

    <div class="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
      <div class="space-y-1.5">
        <Label for="quiz-title">{{ $t('admin.quizTitle') }}</Label>
        <Input id="quiz-title" v-model="form.title" placeholder="e.g. Biology Mock Exam" :invalid="!!errors.title" />
        <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
      </div>
      <div class="space-y-1.5">
        <Label for="quiz-title-it">{{ $t('admin.quizTitleIt') }}</Label>
        <Input id="quiz-title-it" v-model="form.title_it" :placeholder="$t('admin.quizTitleItPlaceholder')" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="quiz-desc">{{ $t('admin.quizDescription') }}</Label>
          <Textarea id="quiz-desc" v-model="form.description" :rows="2" :placeholder="$t('admin.shortDescription')" />
        </div>
        <div class="space-y-1.5">
          <Label for="quiz-desc-it">{{ $t('admin.quizDescriptionIt') }}</Label>
          <Textarea id="quiz-desc-it" v-model="form.description_it" :rows="2" :placeholder="$t('admin.quizTitleItPlaceholder')" />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <Label>{{ $t('admin.timeLimitMinutes') }}</Label>
          <Input v-model.number="form.time_limit_minutes as never" type="number" placeholder="30" />
        </div>
        <div class="space-y-1.5">
          <Label>{{ $t('admin.difficultyFilter') }}</Label>
          <Select
            v-model="form.difficulty"
            :options="[{ label: $t('admin.any'), value: '' }, { label: $t('difficulty.easy'), value: 'easy' }, { label: $t('difficulty.medium'), value: 'medium' }, { label: $t('difficulty.hard'), value: 'hard' }]"
          />
        </div>
      </div>

      <!-- Mode switch -->
      <div class="grid grid-cols-2 gap-2 rounded-lg border border-border p-1">
        <button
          v-for="m in (['auto', 'manual'] as const)"
          :key="m"
          class="cursor-pointer rounded-md px-3 py-2 text-sm font-medium transition-colors"
          :class="form.mode === m ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
          @click="form.mode = m"
        >
          {{ m === 'auto' ? $t('admin.autoGenerate') : $t('admin.manualSelection') }}
        </button>
      </div>

      <!-- Auto -->
      <div v-if="form.mode === 'auto'" class="space-y-4 rounded-lg bg-muted/40 p-4">
        <div class="space-y-1.5">
          <Label>{{ $t('admin.questionCount') }}</Label>
          <Input v-model.number="form.question_count" type="number" min="1" max="120" :invalid="!!errors.question_count" />
          <p v-if="errors.question_count" class="text-xs text-destructive">{{ errors.question_count }}</p>
        </div>
        <div class="space-y-2">
          <Label>{{ $t('admin.subjectsLeaveEmpty') }} <span class="font-normal text-muted-foreground">({{ $t('admin.leaveEmptyAll') }})</span></Label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="s in subjects"
              :key="s.id"
              class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors"
              :class="form.subject_ids.includes(s.id) ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted'"
              @click="toggleSubject(s.id)"
            >
              <Check v-if="form.subject_ids.includes(s.id)" class="size-3.5" />
              {{ $tf(s, 'name') }}
            </button>
          </div>
        </div>
        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
          <Wand2 class="size-3.5" /> {{ $t('admin.randomDraw', { count: form.question_count }) }}
        </p>
      </div>

      <!-- Manual -->
      <div v-else class="space-y-3">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input v-model="search" :placeholder="$t('admin.searchToAdd')" class="pl-9" />
        </div>
        <p v-if="errors.question_ids" class="text-xs text-destructive">{{ errors.question_ids }}</p>
        <p class="text-xs text-muted-foreground">{{ $t('admin.selectedCount', { count: selectedIds.length }) }}</p>
        <div class="max-h-64 space-y-1.5 overflow-y-auto rounded-lg border border-border p-2">
          <button
            v-for="q in results?.data ?? []"
            :key="q.id"
            class="flex w-full cursor-pointer items-start gap-2.5 rounded-md p-2 text-left text-sm transition-colors hover:bg-muted"
            @click="toggleQuestion(q.id)"
          >
            <span
              class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border"
              :class="selectedIds.includes(q.id) ? 'border-primary bg-primary text-primary-foreground' : 'border-border'"
            >
              <Check v-if="selectedIds.includes(q.id)" class="size-3.5" />
            </span>
            <span class="line-clamp-2">{{ $tf(q, 'text') }}</span>
          </button>
          <p v-if="!(results?.data ?? []).length" class="py-4 text-center text-xs text-muted-foreground">{{ $t('admin.typeToSearch') }}</p>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm">
        <Switch v-model="form.is_published" /> <span class="text-muted-foreground">{{ $t('admin.publishNow') }}</span>
      </label>
    </div>

    <div class="mt-5 flex justify-end gap-2 border-t border-border pt-4">
      <Button variant="outline" @click="emit('update:open', false)">{{ $t('admin.cancel') }}</Button>
      <Button :loading="saving" @click="submit">{{ $t('admin.createQuiz') }}</Button>
    </div>
  </Modal>
</template>
