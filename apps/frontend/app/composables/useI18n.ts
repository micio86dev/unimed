import en from '~/i18n/en'
import it from '~/i18n/it'

export type Locale = 'it' | 'en'

const dictionaries: Record<Locale, unknown> = { en, it }

function resolve(obj: unknown, path: string): string | undefined {
  const value = path.split('.').reduce<unknown>((acc, key) => {
    if (acc && typeof acc === 'object' && key in (acc as Record<string, unknown>)) {
      return (acc as Record<string, unknown>)[key]
    }
    return undefined
  }, obj)
  return typeof value === 'string' ? value : undefined
}

/**
 * Lightweight i18n for the SPA. Holds the active locale in shared Nuxt state,
 * resolves dotted message keys (falling back to English then the key itself),
 * and exposes `tf` to pick the right field of a bilingual API object
 * (e.g. `tf(question, 'text')` → `text_it` in Italian, `text` otherwise).
 */
export function useI18n() {
  const locale = useState<Locale>('locale', () => 'it')

  function t(key: string, params?: Record<string, string | number>): string {
    let message = resolve(dictionaries[locale.value], key) ?? resolve(dictionaries.en, key) ?? key
    if (params) {
      for (const [name, value] of Object.entries(params)) {
        message = message.replaceAll(`{${name}}`, String(value))
      }
    }
    return message
  }

  function tf(obj: object | null | undefined, field: string): string {
    if (!obj) return ''
    const record = obj as Record<string, unknown>
    if (locale.value === 'it') {
      const italian = record[`${field}_it`]
      if (typeof italian === 'string' && italian !== '') return italian
    }
    const base = record[field]
    return typeof base === 'string' ? base : ''
  }

  function setLocale(next: Locale): void {
    locale.value = next
    if (import.meta.client) {
      window.localStorage.setItem('unimed_locale', next)
      document.documentElement.lang = next
    }
  }

  const locales: Locale[] = ['it', 'en']

  return { locale, t, tf, setLocale, locales }
}
