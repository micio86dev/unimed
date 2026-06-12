<script setup lang="ts">
import { History, LayoutDashboard, ListChecks, Trophy } from 'lucide-vue-next'
import type { NavItem } from '~/components/AppShell.vue'

const nav: NavItem[] = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Quizzes', to: '/quizzes', icon: ListChecks },
  { label: 'Rankings', to: '/rankings', icon: Trophy },
  { label: 'My attempts', to: '/history', icon: History },
]
</script>

<template>
  <AppShell :nav="nav">
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppShell>
</template>
