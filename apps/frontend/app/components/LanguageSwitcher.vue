<script setup lang="ts">
import { Languages } from 'lucide-vue-next'

const { locale, setLocale, locales } = useI18n()
</script>

<template>
  <div class="inline-flex items-center gap-1 rounded-lg border border-border bg-card p-0.5" role="group" :aria-label="$t('locale.label')">
    <Languages class="ml-1.5 size-3.5 text-muted-foreground" />
    <button
      v-for="l in locales"
      :key="l"
      type="button"
      class="cursor-pointer rounded-md px-2 py-1 text-xs font-semibold uppercase transition-colors"
      :class="locale === l
        ? 'bg-primary text-primary-foreground'
        : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
      :aria-pressed="locale === l"
      @click="setLocale(l)"
    >
      {{ l.toUpperCase() }}
    </button>
  </div>
</template>
