<script setup lang="ts">
import { GraduationCap } from 'lucide-vue-next'
withDefaults(defineProps<{ showText?: boolean }>(), { showText: true })
</script>

<template>
  <NuxtLink to="/" class="flex items-center gap-2.5">
    <span class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
      <GraduationCap class="size-5" />
    </span>
    <span v-if="showText" class="text-lg font-bold tracking-tight">
      Uni<span class="text-primary">Med</span>
    </span>
  </NuxtLink>
</template>
