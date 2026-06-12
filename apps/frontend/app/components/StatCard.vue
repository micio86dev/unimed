<script setup lang="ts">
import type { Component } from 'vue'

const props = defineProps<{
  label: string
  value: string | number
  icon?: Component
  hint?: string
  tone?: 'primary' | 'success' | 'warning' | 'destructive'
  class?: string
}>()

const toneClasses: Record<string, string> = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/12 text-success',
  warning: 'bg-warning/15 text-warning',
  destructive: 'bg-destructive/12 text-destructive',
}
</script>

<template>
  <Card :class="cn('p-5', props.class)" hover>
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="truncate text-sm font-medium text-muted-foreground">{{ label }}</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>
      </div>
      <div
        v-if="icon"
        :class="cn('flex size-10 shrink-0 items-center justify-center rounded-lg', toneClasses[tone ?? 'primary'])"
      >
        <component :is="icon" class="size-5" />
      </div>
    </div>
  </Card>
</template>
