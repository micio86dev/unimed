<script setup lang="ts">
import type { User } from '~/types'

const props = defineProps<{ open: boolean; user?: User | null }>()
const emit = defineEmits<{ 'update:open': [value: boolean]; saved: [] }>()

const { t } = useI18n()
const { success, error: toastError } = useToast()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'student' as 'student' | 'admin',
  is_active: true,
})
const errors = ref<Record<string, string>>({})
const saving = ref(false)
const isEdit = computed(() => !!props.user)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    errors.value = {}
    if (props.user) {
      form.name = props.user.name
      form.email = props.user.email
      form.password = ''
      form.password_confirmation = ''
      form.role = props.user.roles.includes('admin') ? 'admin' : 'student'
      form.is_active = props.user.is_active
    } else {
      Object.assign(form, {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'student',
        is_active: true,
      })
    }
  },
)

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const api = useApi()
    const base: Record<string, unknown> = {
      name: form.name,
      email: form.email,
      role: form.role,
      is_active: form.is_active,
    }
    if (form.password) {
      base.password = form.password
      base.password_confirmation = form.password_confirmation
    }
    if (isEdit.value && props.user) {
      await api(`/admin/users/${props.user.id}`, { method: 'PUT', body: base })
    } else {
      await api('/admin/users', { method: 'POST', body: base })
    }
    success(isEdit.value ? t('admin.userUpdated') : t('admin.userCreated'))
    emit('saved')
    emit('update:open', false)
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (!Object.keys(errors.value).length) toastError(t('admin.couldNotSave'), apiErrorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal :open="open" @update:open="emit('update:open', $event)">
    <div class="mb-4">
      <h2 class="text-lg font-semibold tracking-tight">{{ isEdit ? $t('admin.editUser') : $t('admin.newUserTitle') }}</h2>
      <p class="text-sm text-muted-foreground">{{ isEdit ? $t('admin.updateAccount') : $t('admin.createAccountDesc') }}</p>
    </div>

    <div class="space-y-4">
      <div class="space-y-1.5">
        <Label for="u-name">{{ $t('admin.fullName') }}</Label>
        <Input id="u-name" v-model="form.name" placeholder="Jane Doe" :invalid="!!errors.name" />
        <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
      </div>
      <div class="space-y-1.5">
        <Label for="u-email">{{ $t('admin.email') }}</Label>
        <Input id="u-email" v-model="form.email" type="email" placeholder="jane@example.com" :invalid="!!errors.email" />
        <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <Label>{{ $t('admin.role') }}</Label>
          <Select v-model="form.role" :options="[{ label: $t('nav.student'), value: 'student' }, { label: $t('nav.admin'), value: 'admin' }]" />
        </div>
        <div class="flex items-end pb-2">
          <label class="flex items-center gap-2 text-sm">
            <Switch v-model="form.is_active" /> <span class="text-muted-foreground">{{ $t('admin.active') }}</span>
          </label>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <Label for="u-pass">{{ isEdit ? $t('auth.newPassword') : $t('admin.password') }}</Label>
          <Input id="u-pass" v-model="form.password" type="password" :placeholder="isEdit ? $t('admin.leaveBlankKeep') : '••••••••'" :invalid="!!errors.password" />
          <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
        </div>
        <div class="space-y-1.5">
          <Label for="u-pass2">{{ $t('admin.confirm') }}</Label>
          <Input id="u-pass2" v-model="form.password_confirmation" type="password" placeholder="••••••••" />
        </div>
      </div>
    </div>

    <div class="mt-5 flex justify-end gap-2 border-t border-border pt-4">
      <Button variant="outline" @click="emit('update:open', false)">{{ $t('admin.cancel') }}</Button>
      <Button :loading="saving" @click="submit">{{ isEdit ? $t('admin.saveChanges') : $t('admin.createUser') }}</Button>
    </div>
  </Modal>
</template>
