<script setup lang="ts">
import { ChevronRight, Home } from 'lucide-vue-next'

interface Crumb {
  label: string
  to?: string
}

const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()

const trail = computed<Crumb[]>(() => {
  const p = route.path
  const home: Crumb = auth.isAdmin
    ? { label: t('nav.overview'), to: '/admin' }
    : { label: t('nav.dashboard'), to: '/dashboard' }

  if (p === '/dashboard') return [{ label: t('nav.dashboard') }]
  if (p === '/admin') return [{ label: t('nav.overview') }]
  if (p === '/quizzes') return [home, { label: t('nav.quizzes') }]
  if (p.startsWith('/quizzes/'))
    return [home, { label: t('nav.quizzes'), to: '/quizzes' }, { label: t('breadcrumb.quiz') }]
  if (p === '/rankings') return [home, { label: t('nav.rankings') }]
  if (p === '/history') return [home, { label: t('nav.history') }]
  if (p.startsWith('/results/'))
    return [home, { label: t('nav.history'), to: '/history' }, { label: t('breadcrumb.result') }]
  if (p === '/admin/questions') return [home, { label: t('nav.questions') }]
  if (p === '/admin/quizzes') return [home, { label: t('nav.quizzes') }]
  if (p === '/admin/users') return [home, { label: t('nav.students') }]
  return [home]
})
</script>

<template>
  <nav class="flex items-center gap-1.5 text-sm" :aria-label="t('breadcrumb.home')">
    <NuxtLink :to="auth.isAdmin ? '/admin' : '/dashboard'" class="flex items-center text-muted-foreground hover:text-foreground" :aria-label="t('breadcrumb.home')">
      <Home class="size-4" />
    </NuxtLink>
    <template v-for="(crumb, i) in trail" :key="i">
      <ChevronRight class="size-3.5 shrink-0 text-muted-foreground/60" />
      <NuxtLink
        v-if="crumb.to && i < trail.length - 1"
        :to="crumb.to"
        class="font-medium text-muted-foreground hover:text-foreground"
      >
        {{ crumb.label }}
      </NuxtLink>
      <span v-else class="font-semibold text-foreground">{{ crumb.label }}</span>
    </template>
  </nav>
</template>
