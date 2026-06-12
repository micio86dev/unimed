<script setup lang="ts">
interface Item {
  label: string
  value: number
  color?: string
  meta?: string
}

const props = withDefaults(defineProps<{ items: Item[]; max?: number; suffix?: string }>(), {
  max: 100,
  suffix: '%',
})

function width(value: number) {
  return `${Math.min(100, (value / props.max) * 100)}%`
}
</script>

<template>
  <div class="space-y-4">
    <div v-for="item in items" :key="item.label" class="space-y-1.5">
      <div class="flex items-baseline justify-between text-sm">
        <span class="flex items-center gap-2 font-medium">
          <span class="size-2.5 rounded-full" :style="{ backgroundColor: item.color ?? '#0F5EFF' }" />
          {{ item.label }}
        </span>
        <span class="tabular-nums text-muted-foreground">
          {{ item.value }}{{ suffix }}
          <span v-if="item.meta" class="ml-1 text-xs">{{ item.meta }}</span>
        </span>
      </div>
      <div class="h-2.5 w-full overflow-hidden rounded-full bg-muted">
        <div
          class="h-full rounded-full transition-[width] duration-700 ease-out"
          :style="{ width: width(item.value), backgroundColor: item.color ?? '#0F5EFF' }"
        />
      </div>
    </div>
  </div>
</template>
