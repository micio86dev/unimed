<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next'
definePageMeta({ layout: 'auth', middleware: 'guest' })

const route = useRoute()
const { t } = useI18n()
const { error: toastError } = useToast()

const form = reactive({
  token: (route.query.token as string) ?? '',
  email: (route.query.email as string) ?? '',
  password: '',
  password_confirmation: '',
})
const errors = ref<Record<string, string>>({})
const loading = ref(false)
const done = ref(false)

async function submit() {
  loading.value = true
  errors.value = {}
  try {
    const api = useApi()
    await api('/auth/reset-password', { method: 'POST', body: form })
    done.value = true
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (Object.keys(errors.value).length === 0)
      toastError(t('auth.couldNotReset'), apiErrorMessage(e))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="!done">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('auth.chooseNewPassword') }}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{{ $t('auth.setStrongPassword', { target: form.email || $t('auth.yourAccount') }) }}</p>

      <form class="mt-8 space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="password">{{ $t('auth.newPassword') }}</Label>
          <Input id="password" v-model="form.password" type="password" placeholder="••••••••" :invalid="!!errors.password" />
          <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
          <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
        </div>
        <div class="space-y-1.5">
          <Label for="confirm">{{ $t('auth.confirmPassword') }}</Label>
          <Input id="confirm" v-model="form.password_confirmation" type="password" placeholder="••••••••" />
        </div>
        <Button type="submit" size="lg" class="w-full" :loading="loading">{{ $t('auth.resetPassword') }}</Button>
      </form>
    </div>

    <div v-else class="text-center">
      <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success/12 text-success">
        <CheckCircle2 class="size-6" />
      </div>
      <h1 class="text-xl font-semibold tracking-tight">{{ $t('auth.passwordUpdated') }}</h1>
      <p class="mt-2 text-sm text-muted-foreground">{{ $t('auth.canSignInNow') }}</p>
      <Button class="mt-6 w-full" size="lg" @click="navigateTo('/login')">{{ $t('auth.backToSignIn') }}</Button>
    </div>
  </div>
</template>
