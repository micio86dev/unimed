<script setup lang="ts">
import { LogOut, Menu, X } from 'lucide-vue-next'
import type { Component } from 'vue'

export interface NavItem {
  label: string
  to: string
  icon: Component
}

defineProps<{ nav: NavItem[]; eyebrow?: string }>()

const auth = useAuthStore()
const route = useRoute()
const mobileOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false
  },
)

function isActive(to: string) {
  if (to === '/admin' || to === '/dashboard') return route.path === to
  return route.path === to || route.path.startsWith(`${to}/`)
}

async function logout() {
  await auth.logout()
  navigateTo('/login')
}
</script>

<template>
  <div class="min-h-screen bg-muted/30">
    <!-- Sidebar (desktop) -->
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-border bg-card lg:flex">
      <div class="flex h-16 items-center border-b border-border px-6">
        <AppLogo />
      </div>
      <nav class="flex-1 space-y-1 overflow-y-auto p-4">
        <p v-if="eyebrow" class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ eyebrow }}
        </p>
        <NuxtLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
          :class="isActive(item.to)
            ? 'bg-primary/10 text-primary'
            : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
        >
          <component :is="item.icon" class="size-[18px]" />
          {{ item.label }}
        </NuxtLink>
      </nav>
      <div class="border-t border-border p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
          <Avatar :name="auth.user?.name" :src="auth.user?.avatar_url" />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
            <p class="truncate text-xs text-muted-foreground">{{ auth.user?.email }}</p>
          </div>
          <button
            class="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-destructive"
            :title="$t('nav.signOut')"
            @click="logout"
          >
            <LogOut class="size-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-foreground/40 lg:hidden" @click="mobileOpen = false" />
    </Transition>
    <Transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="-translate-x-full"
      leave-active-class="transition-transform duration-150 ease-in"
      leave-to-class="-translate-x-full"
    >
      <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-border bg-card lg:hidden">
        <div class="flex h-16 items-center justify-between border-b border-border px-5">
          <AppLogo />
          <button class="rounded-md p-1.5 text-muted-foreground hover:bg-muted" @click="mobileOpen = false">
            <X class="size-5" />
          </button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
          <NuxtLink
            v-for="item in nav"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="isActive(item.to)
              ? 'bg-primary/10 text-primary'
              : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
          >
            <component :is="item.icon" class="size-[18px]" />
            {{ item.label }}
          </NuxtLink>
        </nav>
        <div class="border-t border-border p-3">
          <Button variant="ghost" class="w-full justify-start text-destructive" @click="logout">
            <LogOut class="size-4" /> {{ $t('nav.signOut') }}
          </Button>
        </div>
      </aside>
    </Transition>

    <!-- Main -->
    <div class="lg:pl-64">
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-background/80 px-4 backdrop-blur sm:px-6 lg:px-8">
        <button class="rounded-md p-2 text-muted-foreground hover:bg-muted lg:hidden" @click="mobileOpen = true">
          <Menu class="size-5" />
        </button>
        <div class="flex-1">
          <slot name="header">
            <Breadcrumb />
          </slot>
        </div>
        <div class="flex items-center gap-2">
          <slot name="header-actions" />
          <LanguageSwitcher />
          <Avatar :name="auth.user?.name" :src="auth.user?.avatar_url" class="lg:hidden" />
        </div>
      </header>
      <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>
