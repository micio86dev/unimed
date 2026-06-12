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
      <ArrowLeft class="size-4" /> Back to sign in
    </NuxtLink>

    <div v-if="!sent">
      <h1 class="text-2xl font-semibold tracking-tight">Reset your password</h1>
      <p class="mt-1 text-sm text-muted-foreground">
        Enter your email and we'll send you a link to reset your password.
      </p>
      <form class="mt-8 space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">Email</Label>
          <Input id="email" v-model="form.email" type="email" placeholder="you@example.com" :invalid="!!errors.email" />
          <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
        </div>
        <Button type="submit" size="lg" class="w-full" :loading="loading">Send reset link</Button>
      </form>
    </div>

    <div v-else class="text-center">
      <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success/12 text-success">
        <MailCheck class="size-6" />
      </div>
      <h1 class="text-xl font-semibold tracking-tight">Check your inbox</h1>
      <p class="mt-2 text-sm text-muted-foreground">
        If an account exists for <span class="font-medium text-foreground">{{ form.email }}</span>,
        you'll receive a password reset link shortly.
      </p>
    </div>
  </div>
</template>
