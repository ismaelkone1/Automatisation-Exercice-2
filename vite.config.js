import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'public/build',
    rollupOptions: {
      input: {
        main: './assets/script.js',
        styles: './assets/style.css',
      },
    },
  },
});
