/**
 * Resolve the current session once, before the first route renders, so route
 * middleware can rely on the auth state being ready.
 */
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  await auth.init()
})
