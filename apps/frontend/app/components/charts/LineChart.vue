<script setup lang="ts">
const props = withDefaults(
  defineProps<{ values: number[]; max?: number; color?: string; height?: number }>(),
  { max: 100, color: '#0F5EFF', height: 64 },
)

const W = 100
const H = 40

const points = computed(() => {
  const vals = props.values
  if (vals.length === 0) return []
  if (vals.length === 1)
    return [
      { x: 0, y: toY(vals[0]!) },
      { x: W, y: toY(vals[0]!) },
    ]
  const step = W / (vals.length - 1)
  return vals.map((v, i) => ({ x: i * step, y: toY(v) }))
})

function toY(v: number) {
  return H - (Math.min(props.max, Math.max(0, v)) / props.max) * (H - 4) - 2
}

const linePath = computed(() =>
  points.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(2)},${p.y.toFixed(2)}`).join(' '),
)
const areaPath = computed(() => {
  if (points.value.length === 0) return ''
  const first = points.value[0]!
  const last = points.value[points.value.length - 1]!
  return `${linePath.value} L${last.x.toFixed(2)},${H} L${first.x.toFixed(2)},${H} Z`
})
const gradId = `area-${Math.round(props.color.split('').reduce((a, c) => a + c.charCodeAt(0), 0))}`
</script>

<template>
  <div :style="{ height: `${height}px` }" class="w-full">
    <svg :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" class="size-full overflow-visible">
      <defs>
        <linearGradient :id="gradId" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" :stop-color="color" stop-opacity="0.25" />
          <stop offset="100%" :stop-color="color" stop-opacity="0" />
        </linearGradient>
      </defs>
      <path v-if="areaPath" :d="areaPath" :fill="`url(#${gradId})`" />
      <path
        v-if="linePath"
        :d="linePath"
        fill="none"
        :stroke="color"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        vector-effect="non-scaling-stroke"
      />
    </svg>
  </div>
</template>
