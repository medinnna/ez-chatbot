import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import eslintConfigPrettier from 'eslint-config-prettier'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src/sass'),
      '@public': path.resolve(__dirname, './public'),
    },
  },
  build: {
    rollupOptions: {
      input: {
        index: 'src/main.jsx',
        settings: 'src/settings.js',
        conversations: 'src/conversations.js',
        conversation: 'src/conversation.js',
      },
      external: ['ez_chatbot_settings'],
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`,
        globals: { ez_chatbot_settings: 'ez_chatbot_settings' },
      },
    },
  },
  eslintConfigPrettier,
})
