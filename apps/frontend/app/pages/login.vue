<script setup lang="ts">
definePageMeta({ layout: 'auth', middleware: 'guest' })

const auth = useAuthStore()
const route = useRoute()
const { t } = useI18n()
const { error: toastError } = useToast()

const form = reactive({ email: '', password: '', remember: true })
const errors = ref<Record<string, string>>({})
const loading = ref(false)

async function submit() {
  loading.value = true
  errors.value = {}
  try {
    await auth.login(form)
    const redirect = (route.query.redirect as string) || (auth.isAdmin ? '/admin' : '/dashboard')
    await navigateTo(redirect)
  } catch (e) {
    errors.value = apiValidationErrors(e)
    if (Object.keys(errors.value).length === 0) {
      toastError(t('auth.signingIn'), apiErrorMessage(e))
    }
  } finally {
    loading.value = false
  }
}

function fillDemo(role: 'admin' | 'student') {
  form.email = role === 'admin' ? 'admin@unimed.app' : 'student@unimed.app'
  form.password = 'password'
}
</script>

<template>
  <div>
    <div class="mb-8 flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $t('auth.welcomeBack') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ $t('auth.signInToContinue') }}</p>
      </div>
      <LanguageSwitcher />
    </div>

    <form class="space-y-4" @submit.prevent="submit">
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
        <div class="flex items-center justify-between">
          <Label for="password">{{ $t('auth.password') }}</Label>
          <NuxtLink to="/forgot-password" class="text-xs font-medium text-primary hover:underline">
            {{ $t('auth.forgotPassword') }}
          </NuxtLink>
        </div>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="••••••••"
          autocomplete="current-password"
          :invalid="!!errors.password"
        />
        <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
      </div>

      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.remember" type="checkbox" class="size-4 rounded border-input text-primary focus:ring-ring">
        <span class="text-muted-foreground">{{ $t('auth.rememberMe') }}</span>
      </label>

      <Button type="submit" size="lg" class="w-full" :loading="loading">{{ $t('auth.signIn') }}</Button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
      {{ $t('auth.noAccount') }}
      <NuxtLink to="/register" class="font-medium text-primary hover:underline">{{ $t('auth.createAccount') }}</NuxtLink>
    </p>

    <div class="mt-6 rounded-lg border border-dashed border-border bg-muted/40 p-3">
      <p class="text-xs font-medium text-muted-foreground">{{ $t('auth.demoAccounts') }}</p>
      <div class="mt-2 flex gap-2">
        <Button variant="outline" size="sm" class="flex-1" @click="fillDemo('student')">{{ $t('auth.student') }}</Button>
        <Button variant="outline" size="sm" class="flex-1" @click="fillDemo('admin')">{{ $t('auth.admin') }}</Button>
      </div>
    </div>
  </div>
</template>
