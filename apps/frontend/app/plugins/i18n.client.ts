import type { Locale } from '~/composables/useI18n'

/**
 * Initialise the locale on first load: a previously chosen language wins,
 * otherwise we auto-detect from the browser and fall back to Italian. Also
 * exposes `$t` / `$tf` to templates so components don't each need to pull in
 * the composable just to translate static strings.
 */
export default defineNuxtPlugin(() => {
  const { locale, t, tf, setLocale } = useI18n()

  const stored = window.localStorage.getItem('unimed_locale') as Locale | null
  const detected: Locale = (navigator.language || 'it').toLowerCase().startsWith('en') ? 'en' : 'it'
  const initial: Locale = stored === 'en' || stored === 'it' ? stored : detected

  locale.value = initial
  document.documentElement.lang = initial

  return {
    provide: {
      t,
      tf,
      setLocale,
    },
  }
})
