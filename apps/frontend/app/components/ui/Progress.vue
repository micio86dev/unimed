<script setup lang="ts">
import { cn } from '~/lib/utils'

const props = defineProps<{ value?: number; class?: string; indicatorClass?: string }>()
const clamped = computed(() => Math.min(100, Math.max(0, props.value ?? 0)))
</script>

<template>
  <div :class="cn('h-2 w-full overflow-hidden rounded-full bg-muted', props.class)">
    <div
      class="h-full rounded-full bg-primary transition-all duration-500 ease-out"
      :class="indicatorClass"
      :style="{ width: `${clamped}%` }"
    />
  </div>
</template>
