<script setup lang="ts">
import type { Difficulty } from '~/types'

defineProps<{ difficulty: Difficulty }>()
const { t } = useI18n()

const variants: Record<Difficulty, 'success' | 'warning' | 'destructive'> = {
  easy: 'success',
  medium: 'warning',
  hard: 'destructive',
}
</script>

<template>
  <Badge :variant="variants[difficulty]">{{ t(`difficulty.${difficulty}`) }}</Badge>
</template>
