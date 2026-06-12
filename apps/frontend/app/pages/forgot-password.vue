<script setup lang="ts">
import { ArrowLeft, MailCheck } from 'lucide-vue-next'
definePageMeta({ layout: 'auth', middleware: 'guest' })

const form = reactive({ email: '' })
const loading = ref(false)
const sent = ref(false)
const errors = ref<Record<string, string>>({})

async function submit() {
  loading.value = true
  errors.value = {}
  try {
    const api = useApi()
    await api('/auth/forgot-password', { method: 'POST', body: form })
    sent.value = true
  } catch (e) {
    errors.value = apiValidationErrors(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <NuxtLink to="/login" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
      <ArrowLeft class="size-4" /> {{ $t('auth.backToSignIn') }}
    </NuxtLink>

    <div v-if="!sent">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('auth.resetTitle') }}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{{ $t('auth.resetSubtitle') }}</p>
      <form class="mt-8 space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">{{ $t('auth.email') }}</Label>
          <Input id="email" v-model="form.email" type="email" placeholder="you@example.com" :invalid="!!errors.email" />
          <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
        </div>
        <Button type="submit" size="lg" class="w-full" :loading="loading">{{ $t('auth.sendResetLink') }}</Button>
      </form>
    </div>

    <div v-else class="text-center">
      <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success/12 text-success">
        <MailCheck class="size-6" />
      </div>
      <h1 class="text-xl font-semibold tracking-tight">{{ $t('auth.checkInbox') }}</h1>
      <p class="mt-2 text-sm text-muted-foreground">{{ $t('auth.resetSentTo', { email: form.email }) }}</p>
    </div>
  </div>
</template>
