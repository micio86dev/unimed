import { defineStore } from 'pinia'
import type { User } from '~/types'

interface LoginPayload {
  email: string
  password: string
  remember?: boolean
}

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const permissions = ref<string[]>([])
  const ready = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.roles.includes('admin') ?? false)
  const isStudent = computed(() => user.value?.roles.includes('student') ?? false)

  function can(permission: string) {
    return isAdmin.value || permissions.value.includes(permission)
  }

  async function login(payload: LoginPayload) {
    const api = useApi()
    const res = await api<{ data: { token: string; user: User; permissions?: string[] } }>(
      '/auth/login',
      {
        method: 'POST',
        body: payload,
      },
    )
    setToken(res.data.token)
    user.value = res.data.user
    // Permissions come back with the login response — no extra round-trip needed.
    permissions.value = res.data.permissions ?? []
    if (!res.data.permissions) await fetchMe()
  }

  /** Self-service student sign-up. The API returns a token, so we are logged in immediately. */
  async function register(payload: RegisterPayload) {
    const api = useApi()
    const res = await api<{ data: { token: string; user: User; permissions?: string[] } }>(
      '/auth/register',
      {
        method: 'POST',
        body: payload,
      },
    )
    setToken(res.data.token)
    user.value = res.data.user
    permissions.value = res.data.permissions ?? []
    if (!res.data.permissions) await fetchMe()
  }

  function setToken(value: string) {
    useAuthToken().value = value
    useAuthCookie().value = value
  }

  async function fetchMe() {
    const api = useApi()
    try {
      const res = await api<{ data: { user: User; permissions: string[] } }>('/auth/me')
      user.value = res.data.user
      permissions.value = res.data.permissions
    } catch {
      clearSession()
    }
  }

  async function logout() {
    const api = useApi()
    try {
      await api('/auth/logout', { method: 'POST' })
    } catch {
      // ignore network errors on logout
    } finally {
      clearSession()
    }
  }

  function clearSession() {
    useAuthToken().value = null
    useAuthCookie().value = null
    user.value = null
    permissions.value = []
  }

  /** Resolve the current session once on app start (hydrate token from cookie). */
  async function init() {
    if (ready.value) return
    const token = useAuthToken()
    const cookie = useAuthCookie()
    if (!token.value && cookie.value) {
      token.value = cookie.value
    }
    if (token.value) {
      await fetchMe()
    }
    ready.value = true
  }

  return {
    user,
    permissions,
    ready,
    isAuthenticated,
    isAdmin,
    isStudent,
    can,
    login,
    register,
    logout,
    fetchMe,
    clearSession,
    init,
  }
})
