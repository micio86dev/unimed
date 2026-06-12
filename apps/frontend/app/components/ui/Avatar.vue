<script setup lang="ts">
import { initials } from '~/lib/format'
import { cn } from '~/lib/utils'

const props = defineProps<{ name?: string | null; src?: string | null; class?: string }>()
</script>

<template>
  <div
    :class="cn(
      'inline-flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-secondary text-xs font-semibold text-secondary-foreground select-none',
      props.class,
    )"
  >
    <img v-if="src" :src="src" :alt="name ?? ''" class="size-full object-cover">
    <span v-else>{{ initials(name) }}</span>
  </div>
</template>
