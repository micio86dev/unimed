<script setup lang="ts">
import { History, LayoutDashboard, ListChecks, Trophy } from 'lucide-vue-next'
import type { NavItem } from '~/components/AppShell.vue'

const { t } = useI18n()

const nav = computed<NavItem[]>(() => [
  { label: t('nav.dashboard'), to: '/dashboard', icon: LayoutDashboard },
  { label: t('nav.quizzes'), to: '/quizzes', icon: ListChecks },
  { label: t('nav.rankings'), to: '/rankings', icon: Trophy },
  { label: t('nav.history'), to: '/history', icon: History },
])
</script>

<template>
  <AppShell :nav="nav">
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppShell>
</template>
