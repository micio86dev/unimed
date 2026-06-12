<script setup lang="ts">
import { cn } from '~/lib/utils'

const props = defineProps<{ class?: string; hover?: boolean }>()
</script>

<template>
  <div
    :class="cn(
      'rounded-xl border border-border bg-card text-card-foreground shadow-sm',
      hover && 'transition-all hover:shadow-md hover:border-primary/30',
      props.class,
    )"
  >
    <slot />
  </div>
</template>
