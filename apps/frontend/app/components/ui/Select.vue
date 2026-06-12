<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '~/lib/utils'

interface Option {
  label: string
  value: string | number
}

const props = defineProps<{
  modelValue?: string | number | null
  options: Option[]
  placeholder?: string
  disabled?: boolean
  class?: string
  id?: string
}>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div class="relative">
    <select
      :id="id"
      :value="modelValue ?? ''"
      :disabled="disabled"
      :class="cn(
        'h-10 w-full appearance-none rounded-lg border border-input bg-background px-3.5 pr-9 text-sm shadow-sm transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-ring',
        'disabled:cursor-not-allowed disabled:opacity-50',
        modelValue == null || modelValue === '' ? 'text-muted-foreground' : 'text-foreground',
        props.class,
      )"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value" class="text-foreground">
        {{ opt.label }}
      </option>
    </select>
    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
  </div>
</template>
