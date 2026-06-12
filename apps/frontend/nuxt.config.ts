import tailwindcss from '@tailwindcss/vite'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  // Authenticated dashboard product → render as an SPA. Auth is token-based
  // (Sanctum personal access tokens) so the frontend (Vercel) and API (Railway)
  // can live on different domains without cookie/CORS friction.
  ssr: false,

  modules: ['@pinia/nuxt', '@vueuse/nuxt'],

  components: [{ path: '~/components/ui', pathPrefix: false }, '~/components'],

  css: ['~/assets/css/main.css'],

  vite: {
    plugins: [tailwindcss()],
  },

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      appName: 'UniMed',
    },
  },

  app: {
    head: {
      title: 'UniMed — Admission Test Prep',
      htmlAttrs: { lang: 'en' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content:
            'UniMed — prepare for university admission exams with realistic simulations, analytics and rankings.',
        },
        { name: 'theme-color', content: '#0F5EFF' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        },
      ],
    },
    pageTransition: { name: 'page', mode: 'out-in' },
  },

  typescript: {
    strict: true,
    typeCheck: false,
  },

  imports: {
    dirs: ['stores', 'composables', 'lib'],
  },
})
