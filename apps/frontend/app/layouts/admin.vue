<script setup lang="ts">
import { CircleHelp, LayoutDashboard, ListChecks, Users } from 'lucide-vue-next'
import type { NavItem } from '~/components/AppShell.vue'

const nav: NavItem[] = [
  { label: 'Overview', to: '/admin', icon: LayoutDashboard },
  { label: 'Questions', to: '/admin/questions', icon: CircleHelp },
  { label: 'Quizzes', to: '/admin/quizzes', icon: ListChecks },
  { label: 'Users', to: '/admin/users', icon: Users },
]
</script>

<template>
  <AppShell :nav="nav" eyebrow="Administration">
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppShell>
</template>
