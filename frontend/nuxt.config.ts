/**
 * Super Ninety Four storefront — WooNuxt child layer.
 * Parent commerce layer: ./woonuxt_base
 * Child overrides: pages/, layouts/, components/, composables/ (SNF theme UI)
 */
export default defineNuxtConfig({
  extends: ['./woonuxt_base'],

  compatibilityDate: '2025-11-23',
  devtools: { enabled: true },

  // SNF brand + utilities (WooNuxt base also loads its CSS)
  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    wpUrl: process.env.NUXT_WP_URL || process.env.NUXT_PUBLIC_WP_URL || 'https://localhost/super-ninety-four',
    public: {
      wpUrl: process.env.NUXT_PUBLIC_WP_URL || 'https://localhost/super-ninety-four',
      appName: 'Super Ninety Four',
    },
  },

  // Child wins over WooNuxt demo routes for the SNF theme surface
  routeRules: {
    '/': { prerender: false },
    '/expo': { redirect: '/' },
    '/expo/**': { redirect: '/' },
    '/inquiries': { redirect: '/?s=help' },
    '/about': { redirect: '/?s=fit' },
    '/media': { redirect: '/?s=newsletter' },
    '/media/**': { redirect: '/?s=newsletter' },
    '/contact': { redirect: '/' },
    '/newsletter': { redirect: '/?s=newsletter' },
    '/thank-you/newsletter': { prerender: false },
  },

  app: {
    head: {
      title: 'Super Ninety Four',
      htmlAttrs: { lang: 'en' },
      link: [
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=League+Gothic&family=Open+Sans:wght@400;500;600;700&display=swap',
        },
      ],
    },
  },

  nitro: {
    prerender: {
      concurrency: 10,
      interval: 1000,
      failOnError: false,
    },
  },
})
