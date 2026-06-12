<script setup lang="ts">
import { ImagePlus, Loader2, Plus, Trash2, X } from 'lucide-vue-next'
import type { Question, Subject } from '~/types'

const props = defineProps<{ open: boolean; question?: Question | null; subjects: Subject[] }>()
const emit = defineEmits<{ 'update:open': [value: boolean]; saved: [] }>()

const { success, error: toastError } = useToast()

interface AnswerRow {
  text: string
  is_correct: boolean
}

const form = reactive({
  subject_id: null as number | null,
  type: 'single' as 'single' | 'multiple',
  difficulty: 'medium' as 'easy' | 'medium' | 'hard',
  text: '',
  explanation: '' as string | null,
  image_path: null as string | null,
  image_url: null as string | null,
  is_active: true,
  answers: [
    { text: '', is_correct: true },
    { text: '', is_correct: false },
  ] as AnswerRow[],
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
      form.explanation = q.explanation ?? ''
      form.image_path = null
      form.image_url = q.image_url
      form.is_active = q.is_active
      form.answers = (q.answers ?? []).map((a) => ({ text: a.text, is_correct: !!a.is_correct }))
      if (form.answers.length < 2)
        form.answers = [
          { text: '', is_correct: true },
          { text: '', is_correct: false },
        ]
    } else {
      form.subject_id = props.subjects[0]?.id ?? null
      form.type = 'single'
      form.difficulty = 'medium'
      form.text = ''
      form.explanation = ''
      form.image_path = null
      form.image_url = null
      form.is_active = true
      form.answers = [
        { text: '', is_correct: true },
        { text: '', is_correct: false },
      ]
    }
  },
)

function addAnswer() {
  if (form.answers.length < 6) form.answers.push({ text: '', is_correct: false })
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
    toastError('Upload failed', apiErrorMessage(e))
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
      explanation: form.explanation || null,
      image_path: form.image_path,
      is_active: form.is_active,
      answers: form.answers,
    }
    const api = useApi()
    if (isEdit.value && props.question) {
      await api(`/admin/questions/${props.question.id}`, { method: 'PUT', body: payload })
    } else {
      await api('/admin/questions', { method: 'POST', body: payload })
    }
    success(isEdit.value ? 'Question updated' : 'Question created')
    emit('saved')
    emit('update:open', false)
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (!Object.keys(errors.value).length) toastError('Could not save', apiErrorMessage(e))
  } finally {
    saving.value = false
  }
}

const subjectOptions = computed(() => props.subjects.map((s) => ({ label: s.name, value: s.id })))
</script>

<template>
  <Modal :open="open" :class="'max-w-2xl'" @update:open="emit('update:open', $event)">
    <div class="mb-4">
      <h2 class="text-lg font-semibold tracking-tight">{{ isEdit ? 'Edit question' : 'New question' }}</h2>
      <p class="text-sm text-muted-foreground">Fill in the question, its answers, and mark the correct one.</p>
    </div>

    <div class="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="space-y-1.5">
          <Label>Subject</Label>
          <Select v-model="form.subject_id as never" :options="subjectOptions" placeholder="Subject" />
          <p v-if="errors.subject_id" class="text-xs text-destructive">{{ errors.subject_id }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>Difficulty</Label>
          <Select
            v-model="form.difficulty"
            :options="[{ label: 'Easy', value: 'easy' }, { label: 'Medium', value: 'medium' }, { label: 'Hard', value: 'hard' }]"
          />
        </div>
        <div class="space-y-1.5">
          <Label>Type</Label>
          <Select
            v-model="form.type"
            :options="[{ label: 'Single choice', value: 'single' }, { label: 'Multiple choice', value: 'multiple' }]"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="q-text">Question</Label>
        <Textarea id="q-text" v-model="form.text" :rows="3" placeholder="Enter the question stem…" :invalid="!!errors.text" />
        <p v-if="errors.text" class="text-xs text-destructive">{{ errors.text }}</p>
      </div>

      <!-- Answers -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <Label>Answers</Label>
          <span class="text-xs text-muted-foreground">
            {{ form.type === 'single' ? 'Select the one correct answer' : 'Select all correct answers' }}
          </span>
        </div>
        <p v-if="errors.answers" class="text-xs text-destructive">{{ errors.answers }}</p>
        <div v-for="(answer, i) in form.answers" :key="i" class="flex items-center gap-2">
          <button
            type="button"
            class="flex size-7 shrink-0 items-center justify-center border-2 transition-colors"
            :class="[
              form.type === 'single' ? 'rounded-full' : 'rounded-md',
              answer.is_correct ? 'border-success bg-success text-success-foreground' : 'border-border text-transparent hover:border-success/50',
            ]"
            :title="answer.is_correct ? 'Correct answer' : 'Mark as correct'"
            @click="markCorrect(i)"
          >
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5" /></svg>
          </button>
          <Input v-model="answer.text" :placeholder="`Answer ${i + 1}`" class="flex-1" />
          <button
            type="button"
            class="rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:opacity-30"
            :disabled="form.answers.length <= 2"
            @click="removeAnswer(i)"
          >
            <Trash2 class="size-4" />
          </button>
        </div>
        <Button v-if="form.answers.length < 6" variant="ghost" size="sm" @click="addAnswer">
          <Plus class="size-4" /> Add answer
        </Button>
      </div>

      <!-- Explanation (rich text) -->
      <div class="space-y-1.5">
        <Label>Explanation <span class="font-normal text-muted-foreground">(optional)</span></Label>
        <RichTextEditor v-model="form.explanation" />
      </div>

      <!-- Image -->
      <div class="space-y-1.5">
        <Label>Image <span class="font-normal text-muted-foreground">(optional)</span></Label>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile">
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" :disabled="uploading" @click="fileInput?.click()">
            <Loader2 v-if="uploading" class="size-4 animate-spin" />
            <ImagePlus v-else class="size-4" /> Upload image
          </Button>
          <div v-if="form.image_url" class="flex items-center gap-2">
            <img :src="form.image_url" alt="" class="h-10 rounded border border-border">
            <button class="text-muted-foreground hover:text-destructive" @click="form.image_url = null; form.image_path = null">
              <X class="size-4" />
            </button>
          </div>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm">
        <Switch v-model="form.is_active" /> <span class="text-muted-foreground">Active (available for quizzes)</span>
      </label>
    </div>

    <div class="mt-5 flex justify-end gap-2 border-t border-border pt-4">
      <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
      <Button :loading="saving" @click="submit">{{ isEdit ? 'Save changes' : 'Create question' }}</Button>
    </div>
  </Modal>
</template>
