<script setup lang="ts">
import { Atom, BookOpen, Brain, Dna, FlaskConical, Sigma } from 'lucide-vue-next'

const props = defineProps<{
  slug?: string
  color?: string
  class?: string
  size?: 'sm' | 'md' | 'lg'
}>()

const iconMap: Record<string, typeof Atom> = {
  biology: Dna,
  chemistry: FlaskConical,
  physics: Atom,
  mathematics: Sigma,
  logic: Brain,
}

const icon = computed(() => iconMap[props.slug ?? ''] ?? BookOpen)
const box = computed(() => ({ sm: 'size-8', md: 'size-10', lg: 'size-12' })[props.size ?? 'md'])
const inner = computed(() => ({ sm: 'size-4', md: 'size-5', lg: 'size-6' })[props.size ?? 'md'])
</script>

<template>
  <span
    :class="cn('inline-flex items-center justify-center rounded-lg', box, props.class)"
    :style="{ backgroundColor: `${color ?? '#0F5EFF'}1A`, color: color ?? '#0F5EFF' }"
  >
    <component :is="icon" :class="inner" />
  </span>
</template>
