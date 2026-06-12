<script setup lang="ts">
const props = withDefaults(
  defineProps<{ value: number; size?: number; stroke?: number; color?: string; label?: string }>(),
  { size: 160, stroke: 12, color: '#0F5EFF' },
)

const radius = computed(() => (props.size - props.stroke) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
const offset = computed(
  () => circumference.value * (1 - Math.min(100, Math.max(0, props.value)) / 100),
)
</script>

<template>
  <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
    <svg :width="size" :height="size" class="-rotate-90">
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        fill="none"
        :stroke-width="stroke"
        class="stroke-muted"
      />
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        fill="none"
        :stroke="color"
        :stroke-width="stroke"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="offset"
        class="transition-[stroke-dashoffset] duration-1000 ease-out"
      />
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
      <span class="text-3xl font-bold tabular-nums">{{ Math.round(value) }}%</span>
      <span v-if="label" class="mt-0.5 text-xs font-medium text-muted-foreground">{{ label }}</span>
    </div>
  </div>
</template>
