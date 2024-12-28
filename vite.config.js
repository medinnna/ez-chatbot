import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import eslintConfigPrettier from "eslint-config-prettier";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    rollupOptions: {
      external: ['ez_chatbot_settings'],
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`,
        globals: { 'ez_chatbot_settings': 'ez_chatbot_settings' }
      },
    },
  },
  eslintConfigPrettier
})
