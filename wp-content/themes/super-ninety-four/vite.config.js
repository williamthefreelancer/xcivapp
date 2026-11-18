import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const themeRoot = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  root: themeRoot,
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(themeRoot, 'resources/js'),
      '~': path.resolve(themeRoot, 'resources'),
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(themeRoot, 'resources/js/main.js'),
      external: ['jquery'],
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'main.css';
          }
          return 'assets/[name]-[hash][extname]';
        },
        globals: {
          jquery: 'jQuery',
        },
      },
    },
  },
  server: {
    origin: 'http://localhost:5173',
    cors: true,
  },
});
