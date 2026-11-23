import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const themeRoot = path.dirname(fileURLToPath(import.meta.url));

const distCacheHeaders = `# Long-cache built Vite assets
<IfModule mod_headers.c>
	Header set Cache-Control "public, max-age=31536000, immutable"
</IfModule>

<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresByType text/css "access plus 1 year"
	ExpiresByType application/javascript "access plus 1 year"
	ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/css application/javascript
</IfModule>
`;

function writeDistHtaccess() {
  return {
    name: 'snf-write-dist-htaccess',
    closeBundle() {
      fs.writeFileSync(path.join(themeRoot, 'dist', '.htaccess'), distCacheHeaders);
    },
  };
}

export default defineConfig({
  plugins: [vue(), tailwindcss(), writeDistHtaccess()],
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
    cssCodeSplit: true,
    rollupOptions: {
      input: {
        main: path.resolve(themeRoot, 'resources/js/main.js'),
        admin: path.resolve(themeRoot, 'resources/js/admin.js'),
      },
      external: ['jquery'],
      output: {
        format: 'es',
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            const n = assetInfo.name.replace(/\.css$/, '');
            if (n === 'main' || n === 'admin' || n.includes('main') || n.includes('admin')) {
              return n.includes('admin') ? 'admin.css' : 'main.css';
            }
            return '[name][extname]';
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
