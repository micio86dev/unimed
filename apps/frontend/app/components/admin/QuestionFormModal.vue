<script setup lang="ts">
import { ImagePlus, Loader2, Plus, Trash2, X } from 'lucide-vue-next'
import type { Question, Subject } from '~/types'

const props = defineProps<{ open: boolean; question?: Question | null; subjects: Subject[] }>()
const emit = defineEmits<{ 'update:open': [value: boolean]; saved: [] }>()

const { t, tf } = useI18n()
const { success, error: toastError } = useToast()

interface AnswerRow {
  text: string
  text_it: string
  is_correct: boolean
}

function blankAnswers(): AnswerRow[] {
  return [
    { text: '', text_it: '', is_correct: true },
    { text: '', text_it: '', is_correct: false },
  ]
}

const form = reactive({
  subject_id: null as number | null,
  type: 'single' as 'single' | 'multiple',
  difficulty: 'medium' as 'easy' | 'medium' | 'hard',
  text: '',
  text_it: '' as string | null,
  explanation: '' as string | null,
  explanation_it: '' as string | null,
  image_path: null as string | null,
  image_url: null as string | null,
  is_active: true,
  answers: blankAnswers(),
})
const errors = ref<Record<string, string>>({})
const saving = ref(false)
const uploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const isEdit = computed(() => !!props.question)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    errors.value = {}
    if (props.question) {
      const q = props.question
      form.subject_id = q.subject_id
      form.type = q.type
      form.difficulty = q.difficulty
      form.text = q.text
      form.text_it = q.text_it ?? ''
      form.explanation = q.explanation ?? ''
      form.explanation_it = q.explanation_it ?? ''
      form.image_path = null
      form.image_url = q.image_url
      form.is_active = q.is_active
      form.answers = (q.answers ?? []).map((a) => ({
        text: a.text,
        text_it: a.text_it ?? '',
        is_correct: !!a.is_correct,
      }))
      if (form.answers.length < 2) form.answers = blankAnswers()
    } else {
      form.subject_id = props.subjects[0]?.id ?? null
      form.type = 'single'
      form.difficulty = 'medium'
      form.text = ''
      form.text_it = ''
      form.explanation = ''
      form.explanation_it = ''
      form.image_path = null
      form.image_url = null
      form.is_active = true
      form.answers = blankAnswers()
    }
  },
)

function addAnswer() {
  if (form.answers.length < 6) form.answers.push({ text: '', text_it: '', is_correct: false })
}
function removeAnswer(index: number) {
  if (form.answers.length <= 2) return
  const wasCorrect = form.answers[index]?.is_correct
  form.answers.splice(index, 1)
  if (wasCorrect && form.type === 'single' && !form.answers.some((a) => a.is_correct)) {
    form.answers[0]!.is_correct = true
  }
}
function markCorrect(index: number) {
  if (form.type === 'single') {
    form.answers.forEach((a, i) => {
      a.is_correct = i === index
    })
  } else {
    form.answers[index]!.is_correct = !form.answers[index]!.is_correct
  }
}

watch(
  () => form.type,
  (type) => {
    if (type === 'single') {
      const firstCorrect = form.answers.findIndex((a) => a.is_correct)
      const target = firstCorrect === -1 ? 0 : firstCorrect
      form.answers.forEach((a, i) => {
        a.is_correct = i === target
      })
    }
  },
)

async function onFile(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploading.value = true
  try {
    const body = new FormData()
    body.append('image', file)
    const res = await useApi()<{ data: { path: string; url: string } }>('/admin/uploads', {
      method: 'POST',
      body,
    })
    form.image_path = res.data.path
    form.image_url = res.data.url
  } catch (e) {
    toastError(t('admin.uploadFailed'), apiErrorMessage(e))
  } finally {
    uploading.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      subject_id: form.subject_id,
      type: form.type,
      difficulty: form.difficulty,
      text: form.text,
      text_it: form.text_it || null,
      explanation: form.explanation || null,
      explanation_it: form.explanation_it || null,
      image_path: form.image_path,
      is_active: form.is_active,
      answers: form.answers.map((a) => ({
        text: a.text,
        text_it: a.text_it || null,
        is_correct: a.is_correct,
      })),
    }
    const api = useApi()
    if (isEdit.value && props.question) {
      await api(`/admin/questions/${props.question.id}`, { method: 'PUT', body: payload })
    } else {
      await api('/admin/questions', { method: 'POST', body: payload })
    }
    success(isEdit.value ? t('admin.questionUpdated') : t('admin.questionCreated'))
    emit('saved')
    emit('update:open', false)
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (!Object.keys(errors.value).length) toastError(t('admin.couldNotSave'), apiErrorMessage(e))
  } finally {
    saving.value = false
  }
}

const subjectOptions = computed(() =>
  props.subjects.map((s) => ({ label: tf(s, 'name'), value: s.id })),
)
</script>

<template>
  <Modal :open="open" :class="'max-w-2xl'" @update:open="emit('update:open', $event)">
    <div class="mb-4">
      <h2 class="text-lg font-semibold tracking-tight">{{ isEdit ? $t('admin.editQuestion') : $t('admin.newQuestion') }}</h2>
      <p class="text-sm text-muted-foreground">{{ $t('admin.fillInQuestion') }}</p>
    </div>

    <div class="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="space-y-1.5">
          <Label>{{ $t('admin.subject') }}</Label>
          <Select v-model="form.subject_id as never" :options="subjectOptions" :placeholder="$t('admin.subject')" />
          <p v-if="errors.subject_id" class="text-xs text-destructive">{{ errors.subject_id }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>{{ $t('admin.difficulty') }}</Label>
          <Select
            v-model="form.difficulty"
            :options="[{ label: $t('difficulty.easy'), value: 'easy' }, { label: $t('difficulty.medium'), value: 'medium' }, { label: $t('difficulty.hard'), value: 'hard' }]"
          />
        </div>
        <div class="space-y-1.5">
          <Label>{{ $t('admin.type') }}</Label>
          <Select
            v-model="form.type"
            :options="[{ label: $t('admin.single'), value: 'single' }, { label: $t('admin.multiple'), value: 'multiple' }]"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="q-text">{{ $t('admin.questionStem') }}</Label>
        <Textarea id="q-text" v-model="form.text" :rows="2" placeholder="Enter the question stem…" :invalid="!!errors.text" />
        <p v-if="errors.text" class="text-xs text-destructive">{{ errors.text }}</p>
      </div>
      <div class="space-y-1.5">
        <Label for="q-text-it">{{ $t('admin.questionStemIt') }}</Label>
        <Textarea id="q-text-it" v-model="form.text_it as string" :rows="2" :placeholder="$t('admin.questionStemItPlaceholder')" />
      </div>

      <!-- Answers -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <Label>{{ $t('admin.answers') }}</Label>
          <span class="text-xs text-muted-foreground">
            {{ form.type === 'single' ? $t('admin.selectOneCorrect') : $t('admin.selectAllCorrect') }}
          </span>
        </div>
        <p v-if="errors.answers" class="text-xs text-destructive">{{ errors.answers }}</p>
        <div v-for="(answer, i) in form.answers" :key="i" class="flex items-start gap-2">
          <button
            type="button"
            class="mt-1 flex size-7 shrink-0 cursor-pointer items-center justify-center border-2 transition-colors"
            :class="[
              form.type === 'single' ? 'rounded-full' : 'rounded-md',
              answer.is_correct ? 'border-success bg-success text-success-foreground' : 'border-border text-transparent hover:border-success/50',
            ]"
            :title="answer.is_correct ? $t('admin.correctAnswer') : $t('admin.markAsCorrect')"
            @click="markCorrect(i)"
          >
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5" /></svg>
          </button>
          <div class="flex-1 space-y-1.5">
            <Input v-model="answer.text" :placeholder="`Answer ${i + 1}`" />
            <Input v-model="answer.text_it" :placeholder="$t('admin.answerItPlaceholder')" class="text-muted-foreground" />
          </div>
          <button
            type="button"
            class="mt-1 cursor-pointer rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:opacity-30"
            :disabled="form.answers.length <= 2"
            @click="removeAnswer(i)"
          >
            <Trash2 class="size-4" />
          </button>
        </div>
        <Button v-if="form.answers.length < 6" variant="ghost" size="sm" @click="addAnswer">
          <Plus class="size-4" /> {{ $t('admin.addAnswer') }}
        </Button>
      </div>

      <!-- Explanation -->
      <div class="space-y-1.5">
        <Label>{{ $t('admin.explanationLabel') }} <span class="font-normal text-muted-foreground">({{ $t('admin.optional') }})</span></Label>
        <RichTextEditor v-model="form.explanation" />
      </div>
      <div class="space-y-1.5">
        <Label for="q-expl-it">{{ $t('admin.explanationItLabel') }} <span class="font-normal text-muted-foreground">({{ $t('admin.optional') }})</span></Label>
        <Textarea id="q-expl-it" v-model="form.explanation_it as string" :rows="2" :placeholder="$t('admin.questionStemItPlaceholder')" />
      </div>

      <!-- Image -->
      <div class="space-y-1.5">
        <Label>{{ $t('admin.image') }} <span class="font-normal text-muted-foreground">({{ $t('admin.optional') }})</span></Label>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile">
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" :disabled="uploading" @click="fileInput?.click()">
            <Loader2 v-if="uploading" class="size-4 animate-spin" />
            <ImagePlus v-else class="size-4" /> {{ $t('admin.uploadImage') }}
          </Button>
          <div v-if="form.image_url" class="flex items-center gap-2">
            <img :src="form.image_url" alt="" class="h-10 rounded border border-border">
            <button class="cursor-pointer text-muted-foreground hover:text-destructive" @click="form.image_url = null; form.image_path = null">
              <X class="size-4" />
            </button>
          </div>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm">
        <Switch v-model="form.is_active" /> <span class="text-muted-foreground">{{ $t('admin.activeForQuizzes') }}</span>
      </label>
    </div>

    <div class="mt-5 flex justify-end gap-2 border-t border-border pt-4">
      <Button variant="outline" @click="emit('update:open', false)">{{ $t('admin.cancel') }}</Button>
      <Button :loading="saving" @click="submit">{{ isEdit ? $t('admin.saveChanges') : $t('admin.createQuestion') }}</Button>
    </div>
  </Modal>
</template>
