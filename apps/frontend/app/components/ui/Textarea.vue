<script setup lang="ts">
import { cn } from '~/lib/utils'

const props = defineProps<{
  modelValue?: string
  placeholder?: string
  rows?: number
  disabled?: boolean
  invalid?: boolean
  class?: string
  id?: string
}>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <textarea
    :id="id"
    :value="modelValue"
    :placeholder="placeholder"
    :rows="rows ?? 3"
    :disabled="disabled"
    :aria-invalid="invalid || undefined"
    :class="cn(
      'flex w-full rounded-lg border border-input bg-background px-3.5 py-2.5 text-sm shadow-sm transition-colors',
      'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-ring',
      'disabled:cursor-not-allowed disabled:opacity-50',
      invalid && 'border-destructive focus-visible:ring-destructive',
      props.class,
    )"
    @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
  />
</template>
