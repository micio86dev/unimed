<script setup lang="ts">
definePageMeta({ layout: false })

const auth = useAuthStore()

// Single entry point → route users to the right home.
if (import.meta.client) {
  if (!auth.isAuthenticated) navigateTo('/login')
  else navigateTo(auth.isAdmin ? '/admin' : '/dashboard')
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-muted/30">
    <Spinner class="size-8" />
  </div>
</template>
