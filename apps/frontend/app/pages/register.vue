<script setup lang="ts">
definePageMeta({ layout: 'auth', middleware: 'guest' })

const auth = useAuthStore()
const { t } = useI18n()
const { error: toastError } = useToast()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref<Record<string, string>>({})
const loading = ref(false)

async function submit() {
  loading.value = true
  errors.value = {}
  try {
    await auth.register(form)
    // Account is active and we are already authenticated — go straight in.
    await navigateTo('/dashboard')
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (Object.keys(errors.value).length === 0) {
      toastError(t('auth.couldNotRegister'), apiErrorMessage(e))
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('auth.register') }}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{{ $t('auth.registerSubtitle') }}</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="space-y-1.5">
        <Label for="name">{{ $t('auth.name') }}</Label>
        <Input id="name" v-model="form.name" type="text" autocomplete="name" :invalid="!!errors.name" />
        <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="email">{{ $t('auth.email') }}</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          placeholder="you@example.com"
          autocomplete="email"
          :invalid="!!errors.email"
        />
        <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="password">{{ $t('auth.password') }}</Label>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="••••••••"
          autocomplete="new-password"
          :invalid="!!errors.password"
        />
        <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="confirm">{{ $t('auth.confirmPassword') }}</Label>
        <Input
          id="confirm"
          v-model="form.password_confirmation"
          type="password"
          placeholder="••••••••"
          autocomplete="new-password"
        />
      </div>

      <Button type="submit" size="lg" class="w-full" :loading="loading">{{ $t('auth.signUp') }}</Button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
      {{ $t('auth.haveAccount') }}
      <NuxtLink to="/login" class="font-medium text-primary hover:underline">{{ $t('auth.signIn') }}</NuxtLink>
    </p>
  </div>
</template>
