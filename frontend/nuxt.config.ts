import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt'
  ],

  devtools: {
    enabled: true
  },

  css: ['~/assets/css/main.css'],

  compatibilityDate: '2025-01-15',

  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000/api',
      pusherKey: 'local',
      pusherCluster: 'mt1',
      pusherHost: '127.0.0.1',
      pusherPort: '6001',
      pusherScheme: 'http'
    }
  },

  imports: {
    dirs: ['app/stores']
  },

  vite: {
    plugins: [
      tailwindcss()
    ],
    server: {
      watch: {
        usePolling: true,
        interval: 1000
      }
    }
  }
})
