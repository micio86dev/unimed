<script setup lang="ts">
import type { Component } from 'vue'
defineProps<{ icon?: Component; title: string; description?: string }>()
</script>

<template>
  <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border px-6 py-14 text-center">
    <div v-if="icon" class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
      <component :is="icon" class="size-6" />
    </div>
    <h3 class="text-base font-semibold">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
    <div class="mt-5">
      <slot />
    </div>
  </div>
</template>
