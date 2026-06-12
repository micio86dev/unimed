<script setup lang="ts">
import { BarChart3, CheckCircle2, Trophy } from 'lucide-vue-next'

const highlights = [
  { icon: CheckCircle2, text: 'Realistic, timed exam simulations' },
  { icon: BarChart3, text: 'Track your progress by subject' },
  { icon: Trophy, text: 'Compete on the national leaderboard' },
]
</script>

<template>
  <div class="grid min-h-screen lg:grid-cols-2">
    <!-- Brand panel -->
    <div class="relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-foreground lg:flex">
      <div
        class="pointer-events-none absolute inset-0 opacity-30"
        style="background: radial-gradient(60rem 60rem at 80% -10%, rgba(255,255,255,0.35), transparent 60%);"
      />
      <div class="relative">
        <NuxtLink to="/" class="flex items-center gap-2.5">
          <span class="flex size-9 items-center justify-center rounded-xl bg-white/15">
            <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </span>
          <span class="text-lg font-bold tracking-tight">UniMed</span>
        </NuxtLink>
      </div>

      <div class="relative max-w-md">
        <h2 class="text-3xl font-semibold leading-tight tracking-tight">
          Get into medical school with confidence.
        </h2>
        <p class="mt-4 text-primary-foreground/80">
          The complete preparation platform for Medicine, Healthcare Professions and Veterinary admission exams.
        </p>
        <ul class="mt-8 space-y-3">
          <li v-for="h in highlights" :key="h.text" class="flex items-center gap-3">
            <component :is="h.icon" class="size-5 shrink-0" />
            <span class="text-sm text-primary-foreground/90">{{ h.text }}</span>
          </li>
        </ul>
      </div>

      <p class="relative text-sm text-primary-foreground/70">© {{ new Date().getFullYear() }} UniMed. All rights reserved.</p>
    </div>

    <!-- Form panel -->
    <div class="flex items-center justify-center bg-background px-6 py-12">
      <div class="w-full max-w-sm">
        <div class="mb-8 lg:hidden">
          <AppLogo />
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>
