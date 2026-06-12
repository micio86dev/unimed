<script setup lang="ts">
import type { Difficulty } from '~/types'

const props = defineProps<{ difficulty: Difficulty }>()

const map: Record<Difficulty, { label: string; variant: 'success' | 'warning' | 'destructive' }> = {
  easy: { label: 'Easy', variant: 'success' },
  medium: { label: 'Medium', variant: 'warning' },
  hard: { label: 'Hard', variant: 'destructive' },
}
</script>

<template>
  <Badge :variant="map[difficulty].variant">{{ map[difficulty].label }}</Badge>
</template>
