<script setup lang="ts">
import { CheckCircle2, Info, XCircle } from 'lucide-vue-next'

const { toasts, dismiss } = useToast()

const icons = { success: CheckCircle2, error: XCircle, info: Info, default: Info }
const tones: Record<string, string> = {
  success: 'text-success',
  error: 'text-destructive',
  info: 'text-primary',
  default: 'text-foreground',
}
</script>

<template>
  <div class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2">
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0 translate-x-2"
    >
      <div
        v-for="t in toasts"
        :key="t.id"
        class="pointer-events-auto flex items-start gap-3 rounded-xl border border-border bg-card p-4 shadow-lg"
      >
        <component :is="icons[t.variant]" :class="['mt-0.5 size-5 shrink-0', tones[t.variant]]" />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-foreground">{{ t.title }}</p>
          <p v-if="t.description" class="mt-0.5 text-sm text-muted-foreground">{{ t.description }}</p>
        </div>
        <button class="text-muted-foreground hover:text-foreground" @click="dismiss(t.id)">
          <X class="size-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
