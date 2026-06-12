import type { $Fetch } from 'nitropack'

/**
 * In-memory source of truth for the bearer token. Set synchronously on login so
 * the very next request already carries it (a cookie write would not be flushed
 * in time). Persisted to a cookie separately for page reloads.
 */
export function useAuthToken() {
  return useState<string | null>('unimed_token', () => null)
}

/** Cookie used only to persist the token across reloads. */
export function useAuthCookie() {
  return useCookie<string | null>('unimed_token', {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: 'lax',
    secure: import.meta.env.PROD,
    path: '/',
  })
}

/**
 * A pre-configured `$fetch` instance pointed at the Laravel API. Attaches the
 * bearer token, requests JSON, and signs the user out on 401.
 */
export function useApi(): $Fetch {
  const config = useRuntimeConfig()
  const token = useAuthToken()
  const locale = useState<'it' | 'en'>('locale', () => 'it')

  return $fetch.create({
    baseURL: config.public.apiBase,
    headers: { Accept: 'application/json' },
    onRequest({ options }) {
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
      }
      // Let the API localise its messages/validation to the active language.
      options.headers.set('X-Locale', locale.value)
      options.headers.set('Accept-Language', locale.value)
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        const auth = useAuthStore()
        if (auth.isAuthenticated) {
          auth.clearSession()
          if (import.meta.client) {
            navigateTo('/login')
          }
        }
      }
    },
  })
}

/** Extract a human-friendly message from an ofetch error. */
export function apiErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  const e = error as { data?: { message?: string; errors?: Record<string, string[]> } }
  if (e?.data?.errors) {
    const first = Object.values(e.data.errors)[0]
    if (first?.[0]) return first[0]
  }
  return e?.data?.message ?? fallback
}

/** Extract field-level validation errors (422) keyed by field. */
export function apiValidationErrors(error: unknown): Record<string, string> {
  const e = error as { data?: { errors?: Record<string, string[]> } }
  const out: Record<string, string> = {}
  for (const [key, messages] of Object.entries(e?.data?.errors ?? {})) {
    if (messages?.[0]) out[key] = messages[0]
  }
  return out
}
