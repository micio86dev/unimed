<script setup lang="ts">
import { CircleHelp, LayoutDashboard, ListChecks, Users } from 'lucide-vue-next'
import type { NavItem } from '~/components/AppShell.vue'

const { t } = useI18n()

const nav = computed<NavItem[]>(() => [
  { label: t('nav.overview'), to: '/admin', icon: LayoutDashboard },
  { label: t('nav.questions'), to: '/admin/questions', icon: CircleHelp },
  { label: t('nav.quizzes'), to: '/admin/quizzes', icon: ListChecks },
  { label: t('nav.students'), to: '/admin/users', icon: Users },
])
</script>

<template>
  <AppShell :nav="nav" :eyebrow="$t('admin.administration')">
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppShell>
</template>
